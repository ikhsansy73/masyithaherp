<?php

namespace Database\Factories;

use App\Enums\AssessmentDimension;
use App\Enums\AssessmentType;
use App\Models\AcademicTerm;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'classroom_id' => Classroom::factory(),
            'subject_id' => Subject::factory(),
            'teacher_id' => Employee::factory(),
            'academic_term_id' => AcademicTerm::factory(),
            'name' => 'Sumatif '.fake()->word(),
            'type' => AssessmentType::Sumatif,
            'dimension' => AssessmentDimension::Pengetahuan,
            'assessment_date' => today()->subDay(),
            'max_score' => 100,
            'learning_objective_id' => null,
        ];
    }

    public function formatif(): static
    {
        return $this->state(fn (): array => ['type' => AssessmentType::Formatif]);
    }

    public function sumatifAkhir(): static
    {
        return $this->state(fn (): array => ['type' => AssessmentType::SumatifAkhir]);
    }
}
