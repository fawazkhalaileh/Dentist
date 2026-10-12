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
            'title' => 'Dr.',
            'specialty' => 'General dentistry',
            'is_public' => true,
            'sort_order' => 0,
        ];
    }
}
