<?php

namespace Database\Factories;

use App\Enums\ScheduleDay;
use App\Models\AcademicTerm;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'classroom_id' => Classroom::factory(),
            'subject_id' => Subject::factory(),
            'teacher_id' => Employee::factory(),
            'academic_term_id' => AcademicTerm::factory(),

            'day' => ScheduleDay::Senin,
            'start_time' => '07:00',
            'end_time' => '08:00',
        ];
    }
}
