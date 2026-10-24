<?php

namespace Tests\Feature\Clinic;

use App\Actions\Billing\CreateInvoice;
use App\Actions\Billing\RecordPayment;
use App\Actions\Billing\VoidBillingRecord;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BillingEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private User $reception;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reception = User::factory()->receptionist()->withTwoFactor()->create();
        $this->patient = Patient::factory()->create();
        $this->actingAs($this->reception);
        config(['clinic.currency.decimals' => 3]);
    }

    public static function amounts(): array
    {
        return [
            ['25', 25000, '25'],
            ['25.5', 25500, '25.500'],
            ['25.500', 25500, '25.500'],
            ['0.001', 1, '0.001'],
            ['٢٥٫٥٠١', 25501, '25.501'],
            ['25,501', 25501, '25.501'],
        ];
    }

    #[DataProvider('amounts')]
    public function test_jod_parsing_and_formatting_preserve_every_fils(string $typed, int $minor, string $plain): void
    {
        $this->assertSame($minor, Money::parse($typed));
        $this->assertSame($plain, Money::plain($minor));
        $this->assertSame($plain.' JD', Money::format($minor, 'en'));
    }

    public static function invalidAmounts(): array
    {
        return [['-25'], ['-0.001'], ['25.5001'], ['0.0009']];
    }

    #[DataProvider('invalidAmounts')]
    public function test_invalid_amounts_are_rejected_instead_of_rounded(string $typed): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::parse($typed);
    }

    public function test_invoice_numbers_remain_unique_and_sequential_across_patients_and_voids(): void
    {
        $first = $this->invoice();
        app(VoidBillingRecord::class)->handle($first, 'Reissue', $this->reception);
        $other = Patient::factory()->create();
        $second = $this->invoice($other);
        $third = $this->invoice();

        $this->assertSame($first->id + 1, $second->id);
        $this->assertSame($second->id + 1, $third->id);
        foreach ([$first, $second, $third] as $invoice) {
            $this->assertSame('INV-'.str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT), $invoice->number);
        }
        $this->assertSame(3, Invoice::distinct()->count('number'));
    }

    public function test_overpaying_an_invoice_creates_credit_without_losing_fractional_fils(): void
    {
        $invoice = $this->invoice();
        Livewire::test('pages::clinic.patients.billing', ['patient' => $this->patient])
            ->call('openPayment', $invoice->id)
            ->set('payment.amount', '25.501')
            ->call('recordPayment')
            ->assertHasNoErrors()
            ->assertSee('Credit')
            ->assertSee('0.501 JD');

        $this->assertSame(['invoiced' => 25000, 'paid' => 25501, 'balance' => -501], $this->patient->billingSummary());
        $this->assertSame(25501, $invoice->paidAmount());
    }

    public function test_linked_payments_change_invoice_status_from_partly_paid_to_paid_and_back_on_void(): void
    {
        $invoice = $this->invoice();
        $page = Livewire::test('pages::clinic.patients.billing', ['patient' => $this->patient])
            ->call('openPayment', $invoice->id)
            ->set('payment.amount', '24.999')
            ->call('recordPayment')
            ->assertHasNoErrors()
            ->assertSee('Partly paid')
            ->call('openPayment', $invoice->id)
            ->assertSet('payment.amount', '0.001')
            ->call('recordPayment')
            ->assertHasNoErrors()
            ->assertDontSee('Partly paid');

        $this->assertSame(25000, $invoice->paidAmount());
        $this->assertSame(0, $this->patient->billingSummary()['balance']);
        // Check the status badge, since the summary always also says "Paid".
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$page->html());
        $badges = (new \DOMXPath($dom))->query('//*[@data-flux-badge and normalize-space(.)="Paid"]');
        $this->assertNotFalse($badges);
        $this->assertSame(1, $badges->length);
        $page->call('startVoid', 'payment', Payment::latest('id')->firstOrFail()->id)
            ->set('voidReason', 'Final payment reversed')
            ->call('confirmVoid')
            ->assertHasNoErrors()
            ->assertSee('Partly paid');
        $this->assertSame(1, $this->patient->billingSummary()['balance']);
    }

    public function test_voided_records_stay_listed_while_only_valid_records_count(): void
    {
        $voidInvoice = $this->invoice();
        $validInvoice = $this->invoice();
        $voidPayment = app(RecordPayment::class)->handle($this->patient, 1234, PaymentMethod::Cash, now()->toDateString(), $validInvoice, 'VOID-PAYMENT', null, $this->reception);
        app(RecordPayment::class)->handle($this->patient, 5000, PaymentMethod::Cash, now()->toDateString(), $validInvoice, 'VALID-PAYMENT', null, $this->reception);
        app(VoidBillingRecord::class)->handle($voidInvoice, 'Duplicate invoice', $this->reception);
        app(VoidBillingRecord::class)->handle($voidPayment, 'Duplicate payment', $this->reception);

        $this->assertSame(['invoiced' => 25000, 'paid' => 5000, 'balance' => 20000], $this->patient->billingSummary());
        $this->assertSame(5000, $validInvoice->paidAmount());
        Livewire::test('pages::clinic.patients.billing', ['patient' => $this->patient])
            ->assertSee($voidInvoice->number)->assertSee($validInvoice->number)
            ->assertSee('VOID-PAYMENT')->assertSee('VALID-PAYMENT')
            ->assertSee('Duplicate invoice')->assertSee('Duplicate payment');
    }

    public static function billingRoles(): array
    {
        return [['admin'], ['receptionist']];
    }

    #[DataProvider('billingRoles')]
    public function test_billing_managers_can_issue_pay_print_and_void_both_record_types(string $role): void
    {
        $actor = User::factory()->withTwoFactor()->create();
        $actor->assignRole($role);
        $this->actingAs($actor);
        $page = Livewire::test('pages::clinic.patients.billing', ['patient' => $this->patient])
            ->call('openInvoice')->call('addLine')
            ->set('extraLines.0.description', 'Consultation')->set('extraLines.0.price', '25.500')
            ->call('createInvoice')->assertHasNoErrors();
        $invoice = Invoice::sole();
        $page->call('openPayment', $invoice->id)->call('recordPayment')->assertHasNoErrors();
        $this->get(route('clinic.patients.invoices.print', ['patient' => $this->patient, 'invoice' => $invoice]))->assertOk();
        $page->call('startVoid', 'payment', Payment::sole()->id)->set('voidReason', 'Wrong payment')->call('confirmVoid')->assertHasNoErrors()
            ->call('startVoid', 'invoice', $invoice->id)->set('voidReason', 'Wrong invoice')->call('confirmVoid')->assertHasNoErrors();
        $this->assertTrue($invoice->fresh()->isVoid());
        $this->assertTrue(Payment::sole()->isVoid());
        $this->assertSame($actor->id, $invoice->created_by);
    }

    public static function protectedActions(): array
    {
        return [['createInvoice'], ['recordPayment'], ['confirmVoid']];
    }

    #[DataProvider('protectedActions')]
    public function test_each_billing_mutation_rechecks_permission_after_mount(string $action): void
    {
        $invoice = $this->invoice();
        $page = Livewire::test('pages::clinic.patients.billing', ['patient' => $this->patient])
            ->call('openInvoice')->call('addLine')
            ->set('extraLines.0.description', 'Unauthorised line')->set('extraLines.0.price', '10')
            ->call('openPayment', $invoice->id)
            ->call('startVoid', 'invoice', $invoice->id)->set('voidReason', 'Unauthorised void');
        $this->reception->syncRoles(['dentist']);
        $this->patient->dentists()->attach($this->reception);
        $page->call($action)->assertForbidden();
        $this->assertSame(1, Invoice::count());
        $this->assertSame(0, Payment::count());
        $this->assertFalse($invoice->fresh()->isVoid());
    }

    public function test_care_team_dentist_cannot_print_an_invoice(): void
    {
        $invoice = $this->invoice();
        $dentist = User::factory()->dentist()->withTwoFactor()->create();
        $this->patient->dentists()->attach($dentist);
        $this->actingAs($dentist)->get(route('clinic.patients.invoices.print', ['patient' => $this->patient, 'invoice' => $invoice]))->assertForbidden();
    }

    public function test_arabic_patient_billing_tab_displays_jod_amounts(): void
    {
        $this->invoice();
        $this->reception->update(['locale' => 'ar']);
        $this->get(route('clinic.patients.show', ['patient' => $this->patient, 'tab' => 'billing']))
            ->assertOk()->assertSee('dir="rtl"', false)->assertSee('25 د.أ')->assertSee('الفواتير');
    }

    private function invoice(?Patient $patient = null): Invoice
    {
        return app(CreateInvoice::class)->handle($patient ?? $this->patient, [
            ['treatment_item_id' => null, 'description' => 'Consultation', 'quantity' => 1, 'unit_price' => 25000],
        ], now()->toDateString(), null, $this->reception);
    }
}
