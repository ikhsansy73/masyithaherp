<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\Religion;
use App\Enums\StudentStatus;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    private static int $sequence = 0;

    public function definition(): array
    {
        self::$sequence++;

        return [
            'nis' => str_pad((string) self::$sequence, 6, '0', STR_PAD_LEFT),
            // NIS from document_sequences only in services; factories use a
            // per-process sequence to stay unique and fast.
            'full_name' => fake()->name(),
            'gender' => fake()->randomElement([Gender::L, Gender::P]),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->dateTimeBetween('-13 years', '-6 years'),
            'religion' => Religion::Islam,
            'address' => fake()->address(),
            'status' => StudentStatus::Aktif,
            'entry_date' => today(),
        ];
    }

    public function inactive(): static
    {
        $state = fn (): array => ['status' => StudentStatus::Keluar];

        return $this->state($state);
    }
}
