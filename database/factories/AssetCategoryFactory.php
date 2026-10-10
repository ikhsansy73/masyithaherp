<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\AssetCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetCategory>
 */
class AssetCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('???'),
            'name' => fake()->words(2, true),
            'useful_life_months' => 48,
            'asset_account_id' => Account::factory(),
            'is_depreciable' => true,
        ];
    }

    /**
     * Tanah-style category: no depreciation (doc 07 §1).
     */
    public function nonDepreciable(): static
    {
        return $this->state(fn (): array => [
            'useful_life_months' => null,
            'is_depreciable' => false,
        ]);
    }
}
