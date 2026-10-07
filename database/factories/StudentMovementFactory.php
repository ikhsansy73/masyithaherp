<?php

namespace Database\Factories;

use App\Enums\MovementType;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentMovement>
 */
class StudentMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'type' => MovementType::Kenaikan,
            'from_classroom_id' => null,
            'to_classroom_id' => null,
            'movement_date' => today(),
            'notes' => null,
            'registered_by' => User::factory(),
        ];
    }
}
