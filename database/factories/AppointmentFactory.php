<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(1, 30))->setTime(fake()->numberBetween(9, 16), 0);

        return [
            'patient_id' => Patient::factory(),
            'dentist_id' => User::factory()->dentist(),
            'starts_at' => $start,
            'ends_at' => $start->addMinutes(30),
            'status' => AppointmentStatus::Scheduled,
            'source' => 'staff',
        ];
    }
}
