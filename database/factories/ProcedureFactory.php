<?php

namespace Database\Factories;

use App\Models\Procedure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Procedure>
 */
class ProcedureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('PRC-###??')),
            'name' => fake()->words(2, true),
            'category' => 'other',
            'default_duration_minutes' => 30,
            'scope' => 'tooth',
            'is_active' => true,
        ];
    }
}
