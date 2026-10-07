<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentEnrollment>
 */
class StudentEnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            // Bare creates get their own year/classroom; prefer
            // inClassroom() so the enrollment year matches the classroom year.
            'academic_year_id' => AcademicYear::factory(),
            'classroom_id' => Classroom::factory(),
            'grade_level' => 1,
            'status' => EnrollmentStatus::Aktif,
        ];
    }

    /**
     * Enrollment matching a classroom's year and grade.
     */
    public function inClassroom(Classroom $classroom): static
    {
        return $this->state(fn (): array => [
            'academic_year_id' => $classroom->academic_year_id,
            'classroom_id' => $classroom->id,
            'grade_level' => $classroom->grade_level,
            'status' => EnrollmentStatus::Aktif,
        ]);
    }

    public function status(EnrollmentStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
