<?php

namespace Database\Factories;

use App\Enums\GuardianRelation;
use App\Models\Guardian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guardian>
 */
class GuardianFactory extends Factory
{
    public function definition(): array
    {
        return [
            'relationship' => GuardianRelation::Ayah,
            'name' => fake()->name('male'),
            'phone' => fake()->e164PhoneNumber(),
            'is_primary_contact' => true,
        ];
    }

    public function asIbu(): static
    {
        return $this->state(fn (): array => [
            'relationship' => GuardianRelation::Ibu,
            'name' => fake()->name('female'),
            'is_primary_contact' => false,
        ]);
    }

    public function asWali(): static
    {
        return $this->state(fn (): array => [
            'relationship' => GuardianRelation::Wali,
            'is_primary_contact' => false,
        ]);
    }
}
