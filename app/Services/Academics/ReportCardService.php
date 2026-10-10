<?php

namespace App\Services\Academics;

use App\Enums\AssessmentType;
use App\Enums\ReportCardStatus;
use App\Enums\StudentAttendanceStatus;
use App\Exceptions\SchoolException;
use App\Models\AcademicTerm;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Classroom;
use App\Models\LearningObjective;
use App\Models\ReportCard;
use App\Models\ReportCardSubject;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Settings\AcademicSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Rapor write path (doc 06 §7): draft generation (final scores via
 * FinalScoreCalculator, attendance snapshot, auto-drafted editable
 * descriptions) plus the draft → diajukan → disetujui → diterbitkan
 * state machine with a revisi branch.
 */
class ReportCardService
{
    public function __construct(
        private readonly FinalScoreCalculator $calculator,
        private readonly AcademicSettings $settings,
    ) {}

    /**
     * Generate (or regenerate) draft rapor for every active student in
     * the rombel. Existing subject descriptions are preserved (teachers
     * edit the auto-drafts); new subjects get a TP-mastery draft.
     *
     * @return int rapor written
     */
    public function generate(AcademicTerm $term, Classroom $classroom): int
    {
        $students = $classroom->activeEnrollments()->with('student')->get()
            ->map(fn ($enrollment) => $enrollment->student)
            ->filter();

        $subjects = Subject::query()
            ->whereIn('id', $classroom->classSubjectTeachers()->pluck('subject_id'))
            ->orderBy('id')
            ->get();

        return (int) DB::transaction(function () use ($term, $classroom, $students, $subjects): int {
            $written = 0;

            foreach ($students as $student) {
                $existing = ReportCard::query()
                    ->where('student_id', $student->getKey())
                    ->where('academic_term_id', $term->getKey())
                    ->first();

                if ($existing !== null && ! in_array($existing->status, [ReportCardStatus::Draft, ReportCardStatus::Revisi], true)) {
                    continue;
                }

                $card = ReportCard::query()->updateOrCreate(
                    [
                        'student_id' => $student->getKey(),
                        'academic_term_id' => $term->getKey(),
                    ],
                    [
                        'classroom_id' => $classroom->getKey(),
                        'status' => ReportCardStatus::Draft,
                    ],
                );

                $this->syncSubjects($card, $term, $classroom, $student, $subjects);
                $this->syncAttendance($card, $term, $student, $classroom);

                $written++;
            }

            activity()->withProperties(['classroom' => $classroom->name, 'term' => $term->name, 'cards' => $written])
                ->log('Rapor dibuat');

            return $written;
        });
    }

    /**
     * Draft → Diajukan (wali kelas).
     */
    public function submit(ReportCard $card, User $user): void
    {
        $this->assertStatus($card, [ReportCardStatus::Draft, ReportCardStatus::Revisi], 'Rapor hanya bisa diajukan dari status Draft atau Perlu Revisi.');

        $card->forceFill([
            'status' => ReportCardStatus::Diajukan,
            'submitted_at' => now(),
            'revision_note' => null,
        ])->save();

        activity()->performedOn($card)->by($user)->log('Rapor diajukan');
    }

    /**
     * Diajukan → Disetujui (kepala sekolah only; wali kelas has no
     * rapor.approve permission, so the check doubles as that guard).
     */
    public function approve(ReportCard $card, User $user): void
    {
        if (! $user->can('rapor.approve')) {
            throw new SchoolException('Anda tidak memiliki izin menyetujui rapor.');
        }

        $this->assertStatus($card, [ReportCardStatus::Diajukan], 'Hanya rapor berstatus Diajukan yang dapat disetujui.');

        $card->forceFill([
            'status' => ReportCardStatus::Disetujui,
            'reviewed_at' => now(),
            'approved_at' => now(),
            'approved_by' => $user->getKey(),
        ])->save();

        activity()->performedOn($card)->by($user)->log('Rapor disetujui');
    }

    /**
     * Diajukan → Revisi with a mandatory note (kepala sekolah).
     */
    public function requestRevision(ReportCard $card, User $user, string $note): void
    {
        if (! $user->can('rapor.approve')) {
            throw new SchoolException('Anda tidak memiliki izin meminta revisi rapor.');
        }

        if (trim($note) === '') {
            throw new SchoolException('Catatan revisi wajib diisi.');
        }

        $this->assertStatus($card, [ReportCardStatus::Diajukan], 'Hanya rapor berstatus Diajukan yang dapat diminta revisi.');

        $card->forceFill([
            'status' => ReportCardStatus::Revisi,
            'reviewed_at' => now(),
            'revision_note' => trim($note),
        ])->save();

        activity()->performedOn($card)->by($user)->withProperties(['note' => trim($note)])->log('Revisi rapor diminta');
    }

    /**
     * Disetujui → Diterbitkan (kepala sekolah): parents can see it.
     */
    public function publish(ReportCard $card, User $user): void
    {
        if (! $user->can('rapor.publish')) {
            throw new SchoolException('Anda tidak memiliki izin menerbitkan rapor.');
        }

        $this->assertStatus($card, [ReportCardStatus::Disetujui], 'Hanya rapor berstatus Disetujui yang dapat diterbitkan.');

        $card->forceFill([
            'status' => ReportCardStatus::Diterbitkan,
            'published_at' => now(),
            'published_by' => $user->getKey(),
        ])->save();

        activity()->performedOn($card)->by($user)->log('Rapor diterbitkan');
    }

    /**
     * Reject a transition from an unexpected source status.
     *
     * @param  list<ReportCardStatus>  $allowed
     */
    private function assertStatus(ReportCard $card, array $allowed, string $message): void
    {
        if (! in_array($card->status, $allowed, true)) {
            throw new SchoolException($message);
        }
    }

    /**
     * Compute finals per subject and upsert report_card_subjects,
     * preserving any existing (teacher-edited) description.
     *
     * @param  \Illuminate\Support\Collection<int, Subject>  $subjects
     */
    private function syncSubjects(ReportCard $card, AcademicTerm $term, Classroom $classroom, Student $student, \Illuminate\Support\Collection $subjects): void
    {
        foreach ($subjects as $subject) {
            $scores = $this->termScores($term, $classroom, $student, $subject);

            $formative = $scores->get(AssessmentType::Formatif->value, collect());
            $summative = collect()
                ->merge($scores->get(AssessmentType::Sumatif->value, collect()))
                ->merge($scores->get(AssessmentType::SumatifAkhir->value, collect()));

            $final = $this->calculator->compute(
                $formative->map(fn (AssessmentScore $score) => $score->numericValue()),
                $summative->map(fn (AssessmentScore $score) => $score->numericValue()),
            );

            if ($final === null) {
                continue;
            }

            ReportCardSubject::query()->updateOrCreate(
                [
                    'report_card_id' => $card->getKey(),
                    'subject_id' => $subject->getKey(),
                ],
                [
                    'final_score' => round($final, 2),
                    'predicate' => $this->calculator->predicate($final),
                    'description' => $this->existingDescription($card, $subject)
                        ?? $this->draftDescription($term, $classroom, $student, $subject),
                ],
            );
        }
    }

    /**
     * Scores for one student × subject × term keyed by assessment type.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, AssessmentScore>>
     */
    private function termScores(AcademicTerm $term, Classroom $classroom, Student $student, Subject $subject): \Illuminate\Support\Collection
    {
        return AssessmentScore::query()
            ->where('student_id', $student->getKey())
            ->whereHas('assessment', fn ($query) => $query
                ->where('classroom_id', $classroom->getKey())
                ->where('academic_term_id', $term->getKey())
                ->where('subject_id', $subject->getKey()))
            ->with('assessment:id,type')
            ->get()
            ->groupBy(fn (AssessmentScore $score): string => $score->assessment->type->value);
    }

    /**
     * The description already on the card (kept across regeneration).
     */
    private function existingDescription(ReportCard $card, Subject $subject): ?string
    {
        return ReportCardSubject::query()
            ->where('report_card_id', $card->getKey())
            ->where('subject_id', $subject->getKey())
            ->value('description');
    }

    /**
     * Auto-draft: list the TPs the student mastered (average ≥
     * tp_mastery_threshold) with their CP-based descriptions.
     */
    private function draftDescription(AcademicTerm $term, Classroom $classroom, Student $student, Subject $subject): string
    {
        $mastery = AssessmentScore::query()
            ->where('student_id', $student->getKey())
            ->whereHas('assessment', fn ($query) => $query
                ->where('classroom_id', $classroom->getKey())
                ->where('academic_term_id', $term->getKey())
                ->where('subject_id', $subject->getKey())
                ->whereNotNull('learning_objective_id'))
            ->with('assessment:id,learning_objective_id')
            ->get()
            ->groupBy(fn (AssessmentScore $score): int => (int) $score->assessment->learning_objective_id)
            ->map(fn ($rows) => $rows->avg(fn (AssessmentScore $score) => $score->numericValue()));

        $masteredIds = $mastery
            ->filter(fn (float $average): bool => $average >= $this->settings->tp_mastery_threshold)
            ->keys();

        if ($masteredIds->isEmpty()) {
            return 'Perlu bimbingan untuk mencapai tujuan pembelajaran pada mata pelajaran ini.';
        }

        $objectives = LearningObjective::query()
            ->whereIn('id', $masteredIds)
            ->orderBy('code')
            ->get();

        return 'Menunjukkan penguasaan baik pada: '.$objectives
            ->map(fn (LearningObjective $objective): string => $objective->code.' ('.Str::limit($objective->description, 60).')')
            ->implode('; ').'.';
    }

    /**
     * Attendance snapshot over the term window from student_attendances.
     */
    private function syncAttendance(ReportCard $card, AcademicTerm $term, Student $student, Classroom $classroom): void
    {
        $counts = \App\Models\StudentAttendance::query()
            ->where('student_id', $student->getKey())
            ->where('classroom_id', $classroom->getKey())
            ->whereBetween('date', [$term->starts_at->toDateString(), $term->ends_at->toDateString()])
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $card->forceFill([
            'days_sick' => (int) ($counts[StudentAttendanceStatus::Sakit->value] ?? 0),
            'days_izin' => (int) ($counts[StudentAttendanceStatus::Izin->value] ?? 0),
            'days_alpa' => (int) ($counts[StudentAttendanceStatus::Alpa->value] ?? 0),
        ])->save();
    }
}
