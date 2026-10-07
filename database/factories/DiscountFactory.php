<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => \App\Models\Student::factory(),
            'academic_year_id' => \App\Models\AcademicYear::factory(),
            'fee_type_id' => null,
            'name' => 'Beasiswa Prestasi',
            'type' => DiscountType::Percent,
            'value' => 10,
            'start_month' => 7,
            'end_month' => 12,
            'is_active' => true,
            'approved_by' => null,
        ];
    }

    public function fixed(int $amount): static
    {
        return $this->state(fn (): array => [
            'type' => DiscountType::Fixed,
            'value' => $amount,
        ]);
    }

    public function secondSemester(): static
    {
        return $this->state(fn (): array => [
            'start_month' => 1,
            'end_month' => 6,
        ]);
    }
}
