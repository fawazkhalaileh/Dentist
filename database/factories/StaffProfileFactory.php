<?php

namespace Database\Factories;

use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StaffProfile>
 */
class StaffProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->dentist(),
            'slug' => Str::slug(fake()->unique()->name()),
            'is_public' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Adds an English translation using the user's name.
     */
    public function withEnglish(?string $specialty = 'General dentistry'): static
    {
        return $this->afterCreating(function (StaffProfile $profile) use ($specialty) {
            $profile->translations()->create([
                'locale' => 'en',
                'display_name' => $profile->user->name,
                'specialty' => $specialty,
            ]);
        });
    }
}
