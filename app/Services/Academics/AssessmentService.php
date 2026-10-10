<?php

namespace App\Services\Academics;

use App\Enums\EnrollmentStatus;
use App\Enums\LearningPredicate;
use App\Exceptions\SchoolException;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Assessment score write path (doc 06 §6): per student either a numeric
 * score (≤ the assessment max) or a rubric predicate — service validation
 * enforces that one of the two is present.
 */
class AssessmentService
{
    /**
     * Save a class grid. $scores maps student id →
     * ['score' => ?float, 'predicate' => ?string, 'note' => ?string];
     * a row with neither value clears the student's score.
     *
     * @param  array<int, array{score?: mixed, predicate?: mixed, note?: mixed}>  $scores
     * @return int rows written
     */
    public function saveScores(Assessment $assessment, array $scores, User $recorder): int
    {
        $written = (int) DB::transaction(function () use ($assessment, $scores): int {
            $enrolledIds = $assessment->classroom
                ->activeEnrollments()
                ->pluck('student_id');

            $written = 0;

            foreach ($scores as $studentId => $row) {
                $studentId = (int) $studentId;
                $score = $row['score'] ?? null;
                $predicateValue = $row['predicate'] ?? null;
                $note = filled($row['note'] ?? null) ? (string) $row['note'] : null;

                $hasScore = filled($score);
                $hasPredicate = filled($predicateValue);

                if (! $hasScore && ! $hasPredicate) {
                    AssessmentScore::query()
                        ->where('assessment_id', $assessment->getKey())
                        ->where('student_id', $studentId)
                        ->delete();

                    continue;
                }

                if ($hasScore && $hasPredicate) {
                    throw new SchoolException('Isi salah satu saja: nilai angka atau predikat.');
                }

                $student = Student::query()->find($studentId);

                if ($student === null || ! $enrolledIds->contains($studentId)) {
                    throw new SchoolException(
                        'Siswa '.($student?->full_name ?? $studentId).' tidak terdaftar aktif di rombel ini.',
                    );
                }

                if ($hasPredicate) {
                    $predicate = LearningPredicate::tryFrom((string) $predicateValue);

                    if ($predicate === null) {
                        throw new SchoolException("Predikat '{$predicateValue}' tidak valid.");
                    }

                    $predicateValue = $predicate;
                    $score = null;
                } else {
                    $score = (float) $score;

                    if ($score < 0) {
                        throw new SchoolException('Nilai tidak boleh negatif.');
                    }

                    if ($score > (float) $assessment->max_score) {
                        throw new SchoolException(
                            'Nilai tidak boleh melebihi nilai maksimal '.number_format((float) $assessment->max_score, 0, ',', '.').'.',
                        );
                    }

                    $predicateValue = null;
                }

                AssessmentScore::query()->updateOrCreate(
                    [
                        'assessment_id' => $assessment->getKey(),
                        'student_id' => $studentId,
                    ],
                    [
                        'score' => $score,
                        'predicate' => $predicateValue,
                        'note' => $note,
                    ],
                );

                $written++;
            }

            activity()->withProperties(['assessment' => $assessment->name, 'rows' => $written])
                ->log('Nilai disimpan');

            return $written;
        });

        return $written;
    }

    /**
     * Students actively enrolled in the assessment's rombel.
     *
     * @return \Illuminate\Support\Collection<int, Student>
     */
    public function roster(Assessment $assessment): \Illuminate\Support\Collection
    {
        return Student::query()
            ->select(['students.id', 'students.nis', 'students.full_name'])
            ->join('student_enrollments', 'student_enrollments.student_id', '=', 'students.id')
            ->where('student_enrollments.classroom_id', $assessment->classroom_id)
            ->where('student_enrollments.status', EnrollmentStatus::Aktif->value)
            ->orderBy('students.full_name')
            ->get();
    }
}
