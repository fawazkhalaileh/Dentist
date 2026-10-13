<?php

namespace Tests\Feature\Portal;

use App\Enums\AppointmentRequestStatus;
use App\Enums\AppointmentStatus;
use App\Enums\TreatmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\TreatmentItem;
use App\Models\User;
use App\Support\ClinicTime;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PatientPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $account;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        config(['clinic.timezone' => 'Asia/Amman']);
        $this->travelTo(CarbonImmutable::parse('2026-10-12 05:00:00', 'UTC'));
        $this->account = User::factory()->patient()->create(['email' => 'sara@example.test']);
        $this->patient = Patient::factory()->create(['first_name' => 'Sara', 'last_name' => 'Portal', 'phone' => '0791234567']);
    }

    public function test_reception_links_and_unlinks_a_portal_account(): void
    {
        $this->actingAs(User::factory()->receptionist()->withTwoFactor()->create());

        Livewire::test('pages::clinic.patients.show', ['patient' => $this->patient])
            ->set('portalEmail', 'SARA@example.test')
            ->call('linkPortal')
            ->assertHasNoErrors()
            ->assertSee('Linked to sara@example.test');

        $this->assertSame($this->account->id, $this->patient->fresh()?->user_id);
        $this->assertTrue(AuditLog::where('action', 'patient.portal_linked')->exists());

        Livewire::test('pages::clinic.patients.show', ['patient' => $this->patient])->call('unlinkPortal');
        $this->assertNull($this->patient->fresh()?->user_id);
    }

    public function test_only_unlinked_patient_accounts_can_be_linked(): void
    {
        $this->actingAs(User::factory()->receptionist()->withTwoFactor()->create());
        $staff = User::factory()->dentist()->create(['email' => 'dr@example.test']);
        Patient::factory()->withPortalAccount($this->account)->create();

        foreach (['nobody@example.test', $staff->email, 'sara@example.test'] as $email) {
            Livewire::test('pages::clinic.patients.show', ['patient' => $this->patient])
                ->set('portalEmail', $email)
                ->call('linkPortal')
                ->assertHasErrors(['portalEmail']);
        }

        $this->assertNull($this->patient->fresh()?->user_id);
    }

    public function test_dentists_cannot_link_portal_accounts(): void
    {
        $dentist = User::factory()->dentist()->withTwoFactor()->create();
        $this->patient->dentists()->attach($dentist);
        $this->actingAs($dentist);

        Livewire::test('pages::clinic.patients.show', ['patient' => $this->patient])
            ->set('portalEmail', 'sara@example.test')
            ->call('linkPortal')
            ->assertForbidden();
    }

    public function test_an_unlinked_account_sees_an_explanation(): void
    {
        $this->actingAs($this->account);

        $this->get(route('portal.appointments'))->assertOk()->assertSee('not linked to a patient record');
        $this->get(route('portal.treatments'))->assertOk()->assertSee('not linked to a patient record');
    }

    public function test_patients_see_only_their_own_appointments_and_next_one_on_the_dashboard(): void
    {
        $this->link();
        $mine = $this->appointment($this->patient, '2026-10-14', '09:00');
        $this->appointment(Patient::factory()->create(['first_name' => 'Other']), '2026-10-14', '10:00');
        $this->actingAs($this->account);

        $this->get(route('portal.dashboard'))->assertOk()->assertSee('09:00')->assertDontSee('Not built yet');

        Livewire::test('pages::portal.appointments')
            ->assertSee('09:00')
            ->assertDontSee('10:00');

        $other = Appointment::where('patient_id', '!=', $this->patient->id)->sole();
        Livewire::test('pages::portal.appointments')->call('startCancel', $other->id)->assertNotFound();
        $this->assertSame(AppointmentStatus::Scheduled, $mine->fresh()?->status);
    }

    public function test_patients_cancel_future_appointments_with_a_reason(): void
    {
        $this->link();
        $appointment = $this->appointment($this->patient, '2026-10-14', '09:00');
        $this->actingAs($this->account);

        Livewire::test('pages::portal.appointments')
            ->call('startCancel', $appointment->id)
            ->call('confirmCancel')
            ->assertHasErrors(['cancelReason'])
            ->set('cancelReason', 'I am travelling')
            ->call('confirmCancel')
            ->assertHasNoErrors();

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Cancelled, $appointment->status);
        $this->assertSame($this->account->id, $appointment->statusChanges()->latest('id')->value('changed_by'));
    }

    public function test_past_appointments_cannot_be_cancelled_online(): void
    {
        $this->link();
        $past = $this->appointment($this->patient, '2026-10-01', '09:00');
        $this->actingAs($this->account);

        Livewire::test('pages::portal.appointments')->call('startCancel', $past->id)->assertForbidden();
    }

    public function test_patients_request_an_appointment_linked_to_their_record(): void
    {
        $this->link();
        $this->actingAs($this->account);

        Livewire::test('pages::portal.appointments')
            ->call('openRequest')
            ->assertSet('request.phone', '0791234567')
            ->set('request.preferred_time', 'evening')
            ->set('request.message', 'Check-up please')
            ->call('sendRequest')
            ->assertHasNoErrors()
            ->assertSee('waiting for the clinic to call you');

        $request = AppointmentRequest::sole();
        $this->assertSame($this->patient->id, $request->patient_id);
        $this->assertSame('Sara Portal', $request->name);
        $this->assertSame(AppointmentRequestStatus::Pending, $request->status);
    }

    public function test_treatments_show_status_but_no_notes_or_cancelled_items(): void
    {
        $this->link();
        $procedure = Procedure::factory()->create(['name' => 'Filling', 'scope' => 'tooth']);
        TreatmentItem::factory()->create(['patient_id' => $this->patient->id, 'procedure_id' => $procedure->id, 'tooth_number' => 36, 'status' => TreatmentStatus::Planned, 'notes' => 'Internal clinical note']);
        $cancelled = Procedure::factory()->create(['name' => 'Extraction']);
        TreatmentItem::factory()->create(['patient_id' => $this->patient->id, 'procedure_id' => $cancelled->id, 'status' => TreatmentStatus::Cancelled]);
        TreatmentItem::factory()->create(['procedure_id' => Procedure::factory()->create(['name' => 'Someone else crown'])->id]);
        $this->actingAs($this->account);

        $this->get(route('portal.treatments'))
            ->assertOk()
            ->assertSee('Filling')
            ->assertSee('Lower left first molar')
            ->assertDontSee('Internal clinical note')
            ->assertDontSee('Extraction')
            ->assertDontSee('Someone else crown');
    }

    public function test_staff_cannot_open_the_portal_pages(): void
    {
        $this->actingAs(User::factory()->receptionist()->withTwoFactor()->create());

        $this->get(route('portal.appointments'))->assertForbidden();
    }

    public function test_staff_see_the_patient_appointments_tab(): void
    {
        $this->appointment($this->patient, '2026-10-14', '09:00');
        $this->actingAs(User::factory()->receptionist()->withTwoFactor()->create());

        $this->get(route('clinic.patients.appointments', $this->patient))->assertOk()->assertSee('2026-10-14 09:00');
    }

    private function link(): void
    {
        $this->patient->forceFill(['user_id' => $this->account->id])->save();
    }

    private function appointment(Patient $patient, string $date, string $time): Appointment
    {
        $starts = ClinicTime::toUtc($date, $time);

        return Appointment::factory()->create(['patient_id' => $patient->id, 'starts_at' => $starts, 'ends_at' => $starts->addMinutes(30)]);
    }
}
