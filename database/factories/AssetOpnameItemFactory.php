<?php

namespace Database\Factories;

use App\Enums\AssetCondition;
use App\Models\Asset;
use App\Models\AssetOpname;
use App\Models\AssetOpnameItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetOpnameItem>
 */
class AssetOpnameItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'asset_opname_id' => AssetOpname::factory(),
            'asset_id' => Asset::factory(),
            'found' => true,
            'condition' => AssetCondition::Baik,
            'notes' => null,
        ];
    }

    public function missing(): static
    {
        return $this->state(fn (): array => [
            'found' => false,
            'condition' => null,
        ]);
    }
}
