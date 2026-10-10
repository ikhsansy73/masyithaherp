<?php

namespace Database\Factories;

use App\Enums\MaintenanceType;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetMaintenance>
 */
class AssetMaintenanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'maintenance_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'type' => fake()->randomElement(MaintenanceType::cases()),
            'description' => fake()->sentence(),
            'cost' => fake()->numberBetween(1, 50) * 10_000,
            'vendor' => fake()->optional()->company(),
            'journal_entry_id' => null,
        ];
    }
}
