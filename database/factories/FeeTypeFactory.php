<?php

namespace Database\Factories;

use App\Enums\FeeCategory;
use App\Models\FeeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeType>
 */
class FeeTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('???'),
            'name' => fake()->words(2, true),
            'category' => FeeCategory::Bulanan,
            'revenue_account_id' => null,

            'is_active' => true,
        ];
    }

    public function bulanan(): static
    {
        return $this->state(fn (): array => ['category' => FeeCategory::Bulanan]);
    }
}
