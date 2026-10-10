<?php

namespace Database\Factories;

use App\Enums\ReportCardStatus;
use App\Models\AcademicTerm;
use App\Models\Classroom;
use App\Models\ReportCard;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportCard>
 */
class ReportCardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'classroom_id' => Classroom::factory(),
            'academic_term_id' => AcademicTerm::factory(),
            'status' => ReportCardStatus::Draft,
            'revision_note' => null,
            'catatan_wali_kelas' => null,
            'days_sick' => 0,
            'days_izin' => 0,
            'days_alpa' => 0,
            'height_cm' => null,
            'weight_kg' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (): array => [
            'status' => ReportCardStatus::Diajukan,
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => ReportCardStatus::Disetujui,
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now(),
            'approved_at' => now(),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => ReportCardStatus::Diterbitkan,
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now()->subDay(),
            'approved_at' => now()->subDay(),
            'published_at' => now(),
        ]);
    }
}
