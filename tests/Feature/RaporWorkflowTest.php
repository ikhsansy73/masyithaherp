<?php

namespace Tests\Feature;

use App\Enums\ReportCardStatus;
use App\Exceptions\SchoolException;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\User;
use App\Services\Academics\ReportCardService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Full rapor state machine (doc 06 §7): draft → diajukan → disetujui →
 * diterbitkan with the revisi branch, plus permission denials.
 */
class RaporWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Classroom $classroom;

    private AcademicYear $year;

    private User $kepsek;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $this->year = AcademicYear::factory()->create();
        $this->classroom = Classroom::factory()->create([
            'academic_year_id' => $this->year->getKey(),
        ]);

        $this->kepsek = User::factory()->create();
        $this->kepsek->assignRole('kepala_sekolah');
    }

    private function waliKelas(): User
    {
        $user = User::factory()->create();
        $user->assignRole('wali_kelas');
        $employee = Employee::factory()->create(['user_id' => $user->getKey()]);

        $this->classroom->update(['homeroom_teacher_id' => $employee->getKey()]);

        return $user;
    }

    /**
     * One enrolled student plus a scored assessment, so generate() has data.
     */
    private function makeScoredCard(): ReportCard
    {
        $student = Student::factory()->create();
        $student->enrollments()->create([
            'academic_year_id' => $this->year->getKey(),
            'classroom_id' => $this->classroom->getKey(),
            'grade_level' => $this->classroom->grade_level,
            'status' => 'aktif',
        ]);

        $assessment = Assessment::factory()->create([
            'classroom_id' => $this->classroom->getKey(),
            'academic_term_id' => $this->year->terms()->first()->getKey(),
        ]);

        AssessmentScore::query()->create([
            'assessment_id' => $assessment->getKey(),
            'student_id' => $student->getKey(),
            'score' => 80,
        ]);

        $cards = app(ReportCardService::class)->generate(
            $this->year->terms()->first(),
            $this->classroom,
        );

        $this->assertSame(1, $cards);

        return ReportCard::query()->firstOrFail();
    }

    public function test_wali_kelas_submits_draft_and_it_becomes_diajukan(): void
    {
        $wali = $this->waliKelas();
        $card = $this->makeScoredCard();

        $this->assertTrue($card->status === ReportCardStatus::Draft);

        app(ReportCardService::class)->submit($card, $wali);

        $card->refresh();

        $this->assertSame(ReportCardStatus::Diajukan, $card->status);
        $this->assertNotNull($card->submitted_at);
    }

    public function test_kepala_sekolah_approves_submitted_card(): void
    {
        $wali = $this->waliKelas();
        $card = $this->makeScoredCard();
        app(ReportCardService::class)->submit($card, $wali);

        app(ReportCardService::class)->approve($card, $this->kepsek);

        $card->refresh();

        $this->assertSame(ReportCardStatus::Disetujui, $card->status);
        $this->assertSame($this->kepsek->getKey(), $card->approved_by);
        $this->assertNotNull($card->approved_at);
    }

    public function test_wali_kelas_cannot_approve(): void
    {
        $wali = $this->waliKelas();
        $card = $this->makeScoredCard();
        app(ReportCardService::class)->submit($card, $wali);

        $this->expectException(SchoolException::class);
        $this->expectExceptionMessage('tidak memiliki izin menyetujui');

        app(ReportCardService::class)->approve($card, $wali);
    }

    public function test_request_revision_requires_note_and_wali_can_resubmit(): void
    {
        $service = app(ReportCardService::class);
        $wali = $this->waliKelas();
        $card = $this->makeScoredCard();
        $service->submit($card, $wali);

        try {
            $service->requestRevision($card, $this->kepsek, '   ');
            $this->fail('Empty revision note should be rejected.');
        } catch (SchoolException $exception) {
            $this->assertStringContainsString('wajib diisi', $exception->getMessage());
        }

        $service->requestRevision($card, $this->kepsek, 'Deskripsi mapel Matematika belum lengkap.');

        $card->refresh();

        $this->assertSame(ReportCardStatus::Revisi, $card->status);
        $this->assertSame('Deskripsi mapel Matematika belum lengkap.', $card->revision_note);

        $service->submit($card, $wali);
        $card->refresh();

        $this->assertSame(ReportCardStatus::Diajukan, $card->status);
    }

    public function test_publish_requires_disetujui_then_marks_published(): void
    {
        $service = app(ReportCardService::class);
        $wali = $this->waliKelas();
        $card = $this->makeScoredCard();
        $service->submit($card, $wali);

        try {
            $service->publish($card, $this->kepsek);
            $this->fail('Publishing from Diajukan should be rejected.');
        } catch (SchoolException $exception) {
            $this->assertStringContainsString('berstatus Disetujui', $exception->getMessage());
        }

        $service->approve($card, $this->kepsek);
        $service->publish($card, $this->kepsek);

        $card->refresh();

        $this->assertSame(ReportCardStatus::Diterbitkan, $card->status);
        $this->assertNotNull($card->published_at);
        $this->assertTrue($card->isPublished());
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $service = app(ReportCardService::class);
        $wali = $this->waliKelas();
        $card = $this->makeScoredCard();

        try {
            $service->approve($card, $this->kepsek);
            $this->fail('Approving a Draft should be rejected.');
        } catch (SchoolException $exception) {
            $this->assertStringContainsString('berstatus Diajukan', $exception->getMessage());
        }

        $service->submit($card, $wali);

        try {
            $service->submit($card, $wali);
            $this->fail('Submitting twice should be rejected.');
        } catch (SchoolException $exception) {
            $this->assertStringContainsString('Draft atau Perlu Revisi', $exception->getMessage());
        }
    }

    public function test_generate_skips_cards_beyond_draft(): void
    {
        $service = app(ReportCardService::class);
        $wali = $this->waliKelas();
        $card = $this->makeScoredCard();
        $service->submit($card, $wali);
        $service->approve($card, $this->kepsek);

        $written = $service->generate($this->year->terms()->first(), $this->classroom);

        $this->assertSame(0, $written);
        $card->refresh();
        $this->assertSame(ReportCardStatus::Disetujui, $card->status);
    }
}
