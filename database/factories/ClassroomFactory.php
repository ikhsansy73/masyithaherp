<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => '1A',
            'grade_level' => 1,
            'fase' => 'A',
            'capacity' => 28,
            'is_active' => true,
        ];
    }

    /**
     * Grade + rombel letter, fase derived (doc 06 §3).
     */
    public function grade(int $grade, string $letter = 'A'): static
    {
        return $this->state(fn (): array => [
            'name' => $grade.$letter,
            'grade_level' => $grade,
            'fase' => Classroom::faseForGrade($grade),
        ]);
    }
}
