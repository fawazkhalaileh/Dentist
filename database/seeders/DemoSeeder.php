<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\TreatmentEventType;
use App\Enums\TreatmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\StaffProfile;
use App\Models\TreatmentEvent;
use App\Models\TreatmentItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SYNTHETIC demo data for local development only. Never run in production.
 *
 * Demo logins (all use the password "Demo-Password-1!"):
 *   admin@example.test         Administrator
 *   dentist.one@example.test   Dentist (care team for half of the demo patients)
 *   dentist.two@example.test   Dentist (care team for the other half)
 *   reception@example.test     Receptionist
 *   patient@example.test       Patient (linked to a patient record)
 *
 * Staff accounts must set up two-factor authentication on first login unless
 * CLINIC_REQUIRE_STAFF_MFA=false.
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'Demo-Password-1!';

    public function run(): void
    {
        $password = Hash::make(self::PASSWORD);
        $user = fn (string $name, string $email) => ['name' => $name, 'email' => $email, 'password' => $password];

        User::factory()->admin()->create($user('Demo Administrator', 'admin@example.test'));
        $reception = User::factory()->receptionist()->create($user('Demo Receptionist', 'reception@example.test'));
        $dentistOne = User::factory()->dentist()->create($user('Dr. Demo One', 'dentist.one@example.test'));
        $dentistTwo = User::factory()->dentist()->create($user('Dr. Demo Two', 'dentist.two@example.test'));

        foreach ([$dentistOne, $dentistTwo] as $i => $dentist) {
            StaffProfile::factory()->create([
                'user_id' => $dentist->id,
                'slug' => 'demo-dentist-'.($i + 1),
                'sort_order' => $i,
            ]);
        }

        // Patient with a portal account.
        $portalUser = User::factory()->patient()->create($user('Demo Patient', 'patient@example.test'));
        $demoPatient = Patient::factory()
            ->withPortalAccount($portalUser)
            ->assignedTo($dentistOne)
            ->create(['first_name' => 'Demo', 'last_name' => 'Patient', 'email' => 'patient@example.test', 'created_by' => $reception->id]);

        Patient::factory()->count(10)->assignedTo($dentistOne)->create(['created_by' => $reception->id]);
        Patient::factory()->count(10)->assignedTo($dentistTwo)->create(['created_by' => $reception->id]);

        Appointment::factory()->create([
            'patient_id' => $demoPatient->id,
            'dentist_id' => $dentistOne->id,
            'status' => AppointmentStatus::Confirmed,
            'created_by' => $reception->id,
        ]);

        $this->seedExampleTreatment($demoPatient, $dentistOne);
    }

    /**
     * The documented example: a filling on tooth 11, planned then completed,
     * with both lifecycle events preserved.
     */
    private function seedExampleTreatment(Patient $patient, User $dentist): void
    {
        $filling = Procedure::where('code', 'FILL')->firstOrFail();
        $plannedAt = now()->subDays(14);
        $completedAt = now()->subDays(7);

        $item = TreatmentItem::create([
            'patient_id' => $patient->id,
            'tooth_number' => 11,
            'procedure_id' => $filling->id,
            'dentist_id' => $dentist->id,
            'status' => TreatmentStatus::Completed,
            'completed_at' => $completedAt,
            'created_by' => $dentist->id,
        ]);
        $item->surfaces()->create(['surface' => 'M']);

        TreatmentEvent::create([
            'treatment_item_id' => $item->id,
            'type' => TreatmentEventType::Created,
            'to_status' => TreatmentStatus::Planned,
            'performed_by' => $dentist->id,
            'occurred_at' => $plannedAt,
        ]);

        TreatmentEvent::create([
            'treatment_item_id' => $item->id,
            'type' => TreatmentEventType::StatusChanged,
            'from_status' => TreatmentStatus::Planned,
            'to_status' => TreatmentStatus::Completed,
            'performed_by' => $dentist->id,
            'occurred_at' => $completedAt,
        ]);
    }
}
