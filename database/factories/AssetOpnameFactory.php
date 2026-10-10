<?php

namespace Database\Factories;

use App\Models\AssetOpname;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetOpname>
 */
class AssetOpnameFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Opname '.fake()->words(2, true),
            'opname_date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'conducted_by' => User::factory(),
            'notes' => null,
        ];
    }
}
