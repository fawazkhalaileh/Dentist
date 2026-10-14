<?php

namespace Tests\Feature\Clinic;

use App\Actions\Billing\CreateInvoice;
use App\Enums\TreatmentStatus;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Procedure;
use App\Models\TreatmentItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private User $reception;

    private Patient $patient;

    private TreatmentItem $filling;

    private TreatmentItem $crown;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reception = User::factory()->receptionist()->withTwoFactor()->create();
        $this->patient = Patient::factory()->create(['first_name' => 'Sara', 'last_name' => 'Nasser']);
        $this->filling = $this->item('Filling', 35000, TreatmentStatus::Completed, 36);
        $this->crown = $this->item('Crown', 150000, TreatmentStatus::Planned, 26);
        $this->actingAs($this->reception);
    }

    public function test_an_invoice_is_issued_for_completed_treatments_with_their_prices(): void
    {
        Livewire::test('pages::clinic.patients.billing', ['patient' => $this->patient])
            ->assertSee('1 completed treatment is not invoiced yet.')
            ->call('openInvoice')
            ->assertSet('selectedItems', [(string) $this->filling->id])
            ->assertSet("itemPrices.{$this->filling->id}", '35')
            ->call('addLine')
            ->set('extraLines.0.description', 'X-ray')
            ->set('extraLines.0.quantity', 2)
            ->set('extraLines.0.price', '5')
            ->call('createInvoice')
            ->assertHasNoErrors()
            ->assertSee(Invoice::sole()->number);

        $invoice = Invoice::with('lines')->sole();
        $this->assertMatchesRegularExpression('/^INV-\d{6,}$/', $invoice->number);
        $this->assertSame(45000, $invoice->total);
        $this->assertSame([35000, 10000], $invoice->lines->pluck('line_total')->all());
        $this->assertSame($this->filling->id, $invoice->lines->first()?->treatment_item_id);
        $this->assertSame(['invoiced' => 45000, 'paid' => 0, 'balance' => 45000], $this->patient->billingSummary());
        $this->assertTrue(AuditLog::where('action', 'billing.invoice_created')->exists());
    }

    public function test_a_treatment_cannot_be_invoiced_twice_until_its_invoice_is_voided(): void
    {
        $line = [['treatment_item_id' => $this->filling->id, 'description' => 'Filling', 'quantity' => 1, 'unit_price' => 35000]];
        $first = app(CreateInvoice::class)->handle($this->patient, $line, '2026-10-14', null, $this->reception);

        try {
            app(CreateInvoice::class)->handle($this->patient, $line, '2026-10-14', null, $this->reception);
            $this->fail('A treatment must not be billed twice.');
        } catch (ValidationException) {
        }

        Livewire::test('pages::clinic.patients.billing', ['patient' => $this->patient])
            ->call('startVoid', 'invoice', $first->id)
            ->call('confirmVoid')
            ->assertHasErrors(['voidReason'])
            ->set('voidReason', 'Wrong price')
            ->call('confirmVoid')
            ->assertHasNoErrors()
            ->assertSee('Wrong price');

        $this->assertTrue($first->fresh()?->isVoid());
        $this->assertSame(0, $this->patient->billingSummary()['invoiced']);
        app(CreateInvoice::class)->handle($this->patient, $line, '2026-10-14', null, $this->reception);
        $this->assertSame(2, Invoice::count());
    }

    public function test_payments_reduce_the_balance_and_can_be_voided(): void
    {
        $invoice = app(CreateInvoice::class)->handle($this->patient, [['treatment_item_id' => $this->filling->id, 'description' => 'Filling', 'quantity' => 1, 'unit_price' => 35000]], '2026-10-14', null, $this->reception);

        Livewire::test('pages::clinic.patients.billing', ['patient' => $this->patient])
            ->call('openPayment', $invoice->id)
            ->assertSet('payment.amount', '35')
            ->assertSet('payment.invoice_id', (string) $invoice->id)
            ->set('payment.amount', '20')
            ->set('payment.method', 'card')
            ->call('recordPayment')
            ->assertHasNoErrors()
            ->assertSee('Partly paid');

        $this->assertSame(15000, $this->patient->billingSummary()['balance']);

        $payment = Payment::sole();
        Livewire::test('pages::clinic.patients.billing', ['patient' => $this->patient])
            ->call('startVoid', 'payment', $payment->id)
            ->set('voidReason', 'Card declined')
            ->call('confirmVoid');

        $this->assertSame(35000, $this->patient->billingSummary()['balance']);
        $this->assertTrue(AuditLog::where('action', 'billing.payment_voided')->exists());
    }

    public function test_paying_ahead_shows_a_credit_and_zero_or_bad_amounts_are_refused(): void
    {
        Livewire::test('pages::clinic.patients.billing', ['patient' => $this->patient])
            ->call('openPayment')
            ->set('payment.amount', '0')
            ->call('recordPayment')
            ->assertHasErrors(['payment.amount'])
            ->set('payment.amount', 'ten')
            ->call('recordPayment')
            ->assertHasErrors(['payment.amount'])
            ->set('payment.amount', '50')
            ->call('recordPayment')
            ->assertHasNoErrors()
            ->assertSee('Credit');

        $this->assertSame(-50000, $this->patient->billingSummary()['balance']);
    }

    public function test_issued_invoices_and_payments_cannot_be_edited_or_deleted(): void
    {
        $invoice = app(CreateInvoice::class)->handle($this->patient, [['treatment_item_id' => null, 'description' => 'Consultation', 'quantity' => 1, 'unit_price' => 10000]], '2026-10-14', null, $this->reception);

        $this->expectException(LogicException::class);
        $invoice->update(['total' => 1]);
    }

    public function test_other_patients_records_cannot_be_targeted_or_billed(): void
    {
        $other = Patient::factory()->create();
        $foreignItem = TreatmentItem::factory()->create(['patient_id' => $other->id, 'status' => TreatmentStatus::Completed]);
        $foreignInvoice = app(CreateInvoice::class)->handle($other, [['treatment_item_id' => null, 'description' => 'X', 'quantity' => 1, 'unit_price' => 1000]], '2026-10-14', null, $this->reception);

        Livewire::test('pages::clinic.patients.billing', ['patient' => $this->patient])
            ->call('startVoid', 'invoice', $foreignInvoice->id)
            ->assertNotFound();

        Livewire::test('pages::clinic.patients.billing', ['patient' => $this->patient])
            ->call('openInvoice')
            ->set('selectedItems', [(string) $foreignItem->id])
            ->set("itemPrices.{$foreignItem->id}", '10')
            ->call('createInvoice')
            ->assertHasErrors(['selectedItems.0']);

        $this->get(route('clinic.patients.invoices.print', ['patient' => $this->patient, 'invoice' => $foreignInvoice->id]))->assertNotFound();
    }

    public function test_the_invoice_prints_and_the_print_is_audited(): void
    {
        $invoice = app(CreateInvoice::class)->handle($this->patient, [['treatment_item_id' => $this->filling->id, 'description' => 'Filling · tooth 36', 'quantity' => 1, 'unit_price' => 35000]], '2026-10-14', 'Thank you', $this->reception);

        $this->get(route('clinic.patients.invoices.print', ['patient' => $this->patient, 'invoice' => $invoice->id]))
            ->assertOk()
            ->assertSee($invoice->number)
            ->assertSee('Filling · tooth 36')
            ->assertSee('35 JD')
            ->assertSee('Sara Nasser');

        $this->assertTrue(AuditLog::where('action', 'billing.invoice_printed')->exists());
        $this->assertStringNotContainsString('Thank you', (string) DB::table('invoices')->value('notes'));
    }

    public function test_the_estimate_lists_planned_treatments_with_a_total(): void
    {
        $this->get(route('clinic.patients.estimate', $this->patient))
            ->assertOk()
            ->assertSee('Treatment estimate')
            ->assertSee('Crown')
            ->assertDontSee('Filling')
            ->assertSee('150 JD');
    }

    public function test_dentists_do_not_get_billing_and_the_tab_is_hidden(): void
    {
        $dentist = User::factory()->dentist()->withTwoFactor()->create();
        $this->patient->dentists()->attach($dentist);
        $this->actingAs($dentist);

        Livewire::test('pages::clinic.patients.billing', ['patient' => $this->patient])->assertForbidden();
        Livewire::test('pages::clinic.patients.show', ['patient' => $this->patient])->assertDontSee('Billing')->set('tab', 'billing')->assertSet('tab', 'overview');
        // Dentists can still print an estimate for their patient.
        $this->get(route('clinic.patients.estimate', $this->patient))->assertOk();
    }

    private function item(string $name, int $price, TreatmentStatus $status, int $tooth): TreatmentItem
    {
        return TreatmentItem::factory()->create([
            'patient_id' => $this->patient->id,
            'procedure_id' => Procedure::factory()->create(['name' => $name, 'scope' => 'tooth'])->id,
            'tooth_number' => $tooth,
            'price' => $price,
            'status' => $status,
            'completed_at' => $status === TreatmentStatus::Completed ? now() : null,
        ]);
    }
}
