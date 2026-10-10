<?php

namespace Database\Factories;

use App\Models\AccountingPeriod;
use App\Models\Asset;
use App\Models\AssetDepreciation;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetDepreciation>
 */
class AssetDepreciationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'accounting_period_id' => AccountingPeriod::factory(),
            'amount' => fake()->numberBetween(1, 500) * 1000,
            'accumulated_amount' => fake()->numberBetween(1, 500) * 1000,
            'journal_entry_id' => JournalEntry::factory(),
        ];
    }
}
