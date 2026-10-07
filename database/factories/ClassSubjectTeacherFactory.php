<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Employee;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSubjectTeacher>
 */
class ClassSubjectTeacherFactory extends Factory
{
    public function definition(): array
    {
        return [
            'classroom_id' => Classroom::factory(),
            'subject_id' => Subject::factory(),
            'teacher_id' => Employee::factory(),
            'jp_per_week' => null,
        ];
    }
}
