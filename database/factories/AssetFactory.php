<?php

namespace Database\Factories;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\FundingSource;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Fund;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => sprintf('INV-Aset/%s/%06d', now()->format('Y'), fake()->unique()->numberBetween(1, 999999)),
            'name' => fake()->words(2, true),
            'asset_category_id' => AssetCategory::factory(),
            'acquisition_date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'acquisition_cost' => fake()->numberBetween(1, 50) * 100_000,
            'fund_id' => Fund::factory(),
            'funding_source' => FundingSource::Bos,
            'condition' => AssetCondition::Baik,
            'location_id' => Location::factory(),
            'status' => AssetStatus::Aktif,
            'salvage_value' => 0,
        ];
    }

    public function disposed(): static
    {
        return $this->state(fn (): array => [
            'status' => AssetStatus::Dihapuskan,
            'disposal_date' => now()->toDateString(),
        ]);
    }

    public function lost(): static
    {
        return $this->state(fn (): array => [
            'status' => AssetStatus::Hilang,
            'disposal_date' => now()->toDateString(),
        ]);
    }

    public function sold(): static
    {
        return $this->state(fn (): array => [
            'status' => AssetStatus::Dijual,
            'disposal_date' => now()->toDateString(),
        ]);
    }
}
