<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Synthetic patients only. Never seed real patient data.
 *
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-3 years')->format('Y-m-d'),
            'sex' => fake()->randomElement(['female', 'male']),
            'phone' => fake()->numerify('+000 0000 ####'),
            'email' => fake()->unique()->safeEmail(),
            'city' => fake()->city(),
            'preferred_locale' => 'en',
        ];
    }

    public function withPortalAccount(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user !== null ? $user->id : User::factory()->patient(),
        ]);
    }

    /**
     * Assign one or more dentists to the patient's care team.
     */
    public function assignedTo(User ...$dentists): static
    {
        return $this->afterCreating(function (Patient $patient) use ($dentists) {
            foreach ($dentists as $i => $dentist) {
                $patient->dentists()->attach($dentist->id, ['is_primary' => $i === 0]);
            }
        });
    }
}
