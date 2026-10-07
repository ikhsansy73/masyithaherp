<?php

namespace Database\Factories;

use App\Enums\FundType;
use App\Models\Fund;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fund>
 */
class FundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'UMUM',
            'name' => 'Umum/Konsolidasi',
            'type' => FundType::Lainnya,
            'is_active' => true,
        ];
    }

    public function bos(): static
    {
        return $this->state(fn (): array => [
            'code' => 'BOS',
            'name' => 'Dana BOS',
            'type' => FundType::Pemerintah,
        ]);
    }
}
