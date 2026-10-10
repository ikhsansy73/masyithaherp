<?php

namespace Database\Factories;

use App\Models\Extracurricular;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Extracurricular>
 */
class ExtracurricularFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Pramuka', 'Marawis', 'Hadroh', 'Drumband', 'Robotic']),
            'description' => fake()->optional()->sentence(6),
            'is_active' => true,
        ];
    }
}
