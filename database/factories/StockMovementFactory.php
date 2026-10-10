<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'inventory_item_id' => InventoryItem::factory(),
            'movement_date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'type' => StockMovementType::Masuk,
            'quantity' => fake()->numberBetween(1, 20),
            'unit_cost' => fake()->numberBetween(1, 100) * 1000,
            'total_cost' => 0,
            'purpose' => null,
            'requester_id' => null,
            'journal_entry_id' => null,
        ];
    }
}
