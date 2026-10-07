<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\FeeType;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentFee>
 */
class StudentFeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'fee_type_id' => FeeType::factory(),
            'amount' => 600_000,

            'months' => 12,
            'first_month' => 7,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
