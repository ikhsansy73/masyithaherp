<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Employee;
use App\Models\Subject;
use App\Models\TeacherLearningJournal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherLearningJournal>
 */
class TeacherLearningJournalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'classroom_id' => Classroom::factory(),
            'subject_id' => Subject::factory(),
            'teacher_id' => Employee::factory(),
            'date' => today()->subDay(),
            'learning_objective_id' => null,
            'topic' => fake()->sentence(4),
            'method' => fake()->randomElement(['Diskusi', 'Demonstrasi', 'Ceramah', 'Tugas']),
            'notes' => fake()->optional()->sentence(8),
        ];
    }
}
