<?php

namespace Database\Factories;

use App\Models\DocumentSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentSequence>
 */
class DocumentSequenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key' => 'kwitansi',
            'period' => '2026',
            'prefix' => 'KW/2026/',
            'next_number' => 1,
        ];
    }
}
