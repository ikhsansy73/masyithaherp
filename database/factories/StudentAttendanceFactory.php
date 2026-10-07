<?php

namespace Database\Factories;

use App\Enums\StudentAttendanceStatus;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentAttendance>
 */
class StudentAttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'classroom_id' => Classroom::factory(),
            'date' => today(),
            'status' => StudentAttendanceStatus::Hadir,
            'recorded_by' => User::factory(),
        ];
    }

    /**
     * Attendance on a specific date, matching the student's classroom.
     */
    public function forStudentOnDate(Student $student, Classroom $classroom, $date): static
    {
        return $this->state(fn (): array => [
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'date' => $date,
        ]);
    }
}
