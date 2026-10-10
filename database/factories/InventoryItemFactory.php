<?php

namespace Database\Factories;

use App\Enums\InventoryUnit;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('ATK-???'),
            'name' => fake()->words(2, true),
            'unit' => fake()->randomElement(InventoryUnit::cases()),
            'current_stock' => 0,
            'min_stock' => 0,
            'avg_cost' => 0,
            'is_active' => true,
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn (): array => [
            'current_stock' => 2,
            'min_stock' => 5,
        ]);
    }
}
