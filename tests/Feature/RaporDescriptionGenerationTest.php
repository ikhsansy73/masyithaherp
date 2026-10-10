<?php

namespace Tests\Feature;

use App\Enums\LearningPredicate;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Classroom;
use App\Models\LearningAchievement;
use App\Models\LearningObjective;
use App\Models\ReportCard;
use App\Models\ReportCardSubject;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academics\ReportCardService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Auto-drafted descriptions (doc 06 §6-7): finals at 40/60, TP-mastery
 * drafts, regeneration keeps teacher-edited text.
 */
class RaporDescriptionGenerationTest extends TestCase
{
    use RefreshDatabase;

    private AcademicTerm $term;

    private Classroom $classroom;

    private Subject $subject;

    private Student $student;

    private LearningObjective $masteredObjective;

    private LearningObjective $weakObjective;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $year = AcademicYear::factory()->create();
        $this->term = $year->terms()->first();
        $this->classroom = Classroom::factory()->create([
            'academic_year_id' => $year->getKey(),
        ]);
        $this->subject = Subject::factory()->create();
        \App\Models\ClassSubjectTeacher::factory()->create([
            'classroom_id' => $this->classroom->getKey(),
            'subject_id' => $this->subject->getKey(),
        ]);

        $achievement = LearningAchievement::factory()->create([
            'subject_id' => $this->subject->getKey(),
            'fase' => $this->classroom->fase,
        ]);
        $this->masteredObjective = LearningObjective::factory()->create([
            'learning_achievement_id' => $achievement->getKey(),
            'code' => 'A.1.1',
        ]);
        $this->weakObjective = LearningObjective::factory()->create([
            'learning_achievement_id' => $achievement->getKey(),
            'code' => 'A.1.2',
        ]);

        $this->student = Student::factory()->create();
        $this->student->enrollments()->create([
            'academic_year_id' => $year->getKey(),
            'classroom_id' => $this->classroom->getKey(),
            'grade_level' => $this->classroom->grade_level,
            'status' => 'aktif',
        ]);
    }

    /**
     * Formative avg 85, summative avg 75 → final 79 (predikat MB).
     * Objective A.1.1 scores ≥ threshold, A.1.2 below it.
     */
    private function scoreTheStudent(): void
    {
        $formatif = Assessment::factory()->formatif()->create([
            'classroom_id' => $this->classroom->getKey(),
            'subject_id' => $this->subject->getKey(),
            'academic_term_id' => $this->term->getKey(),
            'learning_objective_id' => $this->masteredObjective->getKey(),
        ]);
        $summative = Assessment::factory()->create([
            'classroom_id' => $this->classroom->getKey(),
            'subject_id' => $this->subject->getKey(),
            'academic_term_id' => $this->term->getKey(),
        ]);

        AssessmentScore::query()->create([
            'assessment_id' => $formatif->getKey(),
            'student_id' => $this->student->getKey(),
            'score' => 85,
        ]);
        AssessmentScore::query()->create([
            'assessment_id' => $summative->getKey(),
            'student_id' => $this->student->getKey(),
            'score' => 75,
        ]);
    }

    public function test_final_score_is_40_60_and_predicate_matches(): void
    {
        $this->scoreTheStudent();

        app(ReportCardService::class)->generate($this->term, $this->classroom);

        $row = ReportCardSubject::query()->firstOrFail();

        $this->assertSame(79.0, (float) $row->final_score);
        $this->assertSame(LearningPredicate::MB, $row->predicate);
    }

    public function test_description_lists_mastered_tps_only(): void
    {
        // Master the A.1.1 objective (85 ≥ 75) but leave A.1.2 unscored.
        $this->scoreTheStudent();

        app(ReportCardService::class)->generate($this->term, $this->classroom);

        $description = ReportCardSubject::query()->firstOrFail()->description;

        $this->assertStringContainsString('Menunjukkan penguasaan baik', $description);
        $this->assertStringContainsString($this->masteredObjective->code, $description);
        $this->assertStringNotContainsString($this->weakObjective->code, $description);
    }

    public function test_low_mastery_gets_guidance_draft(): void
    {
        $assessment = Assessment::factory()->formatif()->create([
            'classroom_id' => $this->classroom->getKey(),
            'subject_id' => $this->subject->getKey(),
            'academic_term_id' => $this->term->getKey(),
            'learning_objective_id' => $this->masteredObjective->getKey(),
        ]);

        AssessmentScore::query()->create([
            'assessment_id' => $assessment->getKey(),
            'student_id' => $this->student->getKey(),
            'predicate' => LearningPredicate::BB,
        ]);

        app(ReportCardService::class)->generate($this->term, $this->classroom);

        $description = ReportCardSubject::query()->firstOrFail()->description;

        $this->assertStringContainsString('Perlu bimbingan', $description);
    }

    public function test_regenerate_preserves_teacher_edited_description(): void
    {
        $this->scoreTheStudent();

        $service = app(ReportCardService::class);
        $service->generate($this->term, $this->classroom);

        $row = ReportCardSubject::query()->firstOrFail();
        $row->update(['description' => 'Ananda rajin berlatih perkalian, tingkatkan ketelitian.']);

        $service->generate($this->term, $this->classroom);

        $row->refresh();

        $this->assertSame('Ananda rajin berlatih perkalian, tingkatkan ketelitian.', $row->description);
    }

    public function test_subject_without_scores_is_skipped(): void
    {
        app(ReportCardService::class)->generate($this->term, $this->classroom);

        $this->assertSame(0, ReportCardSubject::query()->count());
        $this->assertSame(1, ReportCard::query()->count());

        $card = ReportCard::query()->firstOrFail();
        $this->assertSame(0, $card->days_sick + $card->days_izin + $card->days_alpa);
    }

    public function test_attendance_snapshot_counts_status_days(): void
    {
        $recorder = User::factory()->create();

        StudentAttendance::factory()->create([
            'student_id' => $this->student->getKey(),
            'classroom_id' => $this->classroom->getKey(),
            'status' => 'sakit',
            'recorded_by' => $recorder->getKey(),
            'date' => $this->term->starts_at->addDays(3),
        ]);
        StudentAttendance::factory()->create([
            'student_id' => $this->student->getKey(),
            'classroom_id' => $this->classroom->getKey(),
            'status' => 'sakit',
            'recorded_by' => $recorder->getKey(),
            'date' => $this->term->starts_at->addDays(5),
        ]);
        StudentAttendance::factory()->create([
            'student_id' => $this->student->getKey(),
            'classroom_id' => $this->classroom->getKey(),
            'status' => 'izin',
            'date' => $this->term->starts_at->addDays(4),
            'recorded_by' => $recorder->getKey(),
        ]);
        StudentAttendance::factory()->create([
            'student_id' => $this->student->getKey(),
            'classroom_id' => $this->classroom->getKey(),
            'status' => 'alpa',
            'date' => $this->term->starts_at->subDays(10),
            'recorded_by' => $recorder->getKey(),
        ]);

        app(ReportCardService::class)->generate($this->term, $this->classroom);

        $card = ReportCard::query()->firstOrFail();

        $this->assertSame(2, $card->days_sick);
        $this->assertSame(1, $card->days_izin);
        $this->assertSame(0, $card->days_alpa);
    }
}
