<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Classroom;
use App\Models\ClassSubjectTeacher;
use App\Models\Employee;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academics\ReportCardService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rapor PDF printing (doc 06 §7): per student and per class batch, with
 * staff row scoping and guardian-only-published access.
 */
class RaporPdfTest extends TestCase
{
    use RefreshDatabase;

    private Classroom $classroom;

    private AcademicYear $year;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $this->year = AcademicYear::factory()->create();
        $this->classroom = Classroom::factory()->create([
            'academic_year_id' => $this->year->getKey(),
        ]);

        $this->student = Student::factory()->create();
        $this->student->enrollments()->create([
            'academic_year_id' => $this->year->getKey(),
            'classroom_id' => $this->classroom->getKey(),
            'grade_level' => $this->classroom->grade_level,
            'status' => 'aktif',
        ]);
    }

    private function staffUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        Employee::factory()->create(['user_id' => $user->getKey()]);

        return $user;
    }

    /**
     * One taught subject with a score, then generate draft cards.
     */
    private function generateCard(): void
    {
        $subject = Subject::factory()->create();
        ClassSubjectTeacher::factory()->create([
            'classroom_id' => $this->classroom->getKey(),
            'subject_id' => $subject->getKey(),
        ]);

        $assessment = Assessment::factory()->create([
            'classroom_id' => $this->classroom->getKey(),
            'academic_term_id' => $this->year->terms()->first()->getKey(),
            'subject_id' => $subject->getKey(),
        ]);
        AssessmentScore::query()->create([
            'assessment_id' => $assessment->getKey(),
            'student_id' => $this->student->getKey(),
            'score' => 82,
        ]);

        app(ReportCardService::class)->generate($this->year->terms()->first(), $this->classroom);
    }

    public function test_kepala_sekolah_prints_single_rapor_pdf(): void
    {
        $this->generateCard();
        $kepsek = $this->staffUser('kepala_sekolah');
        $card = $this->student->reportCards()->firstOrFail();

        $this->actingAs($kepsek)
            ->get("/rapor/{$card->getKey()}/pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_kepala_sekolah_prints_class_batch_pdf(): void
    {
        $this->generateCard();
        $kepsek = $this->staffUser('kepala_sekolah');

        $this->actingAs($kepsek)
            ->get('/rapor/cetak-kelas/'.$this->classroom->getKey().'/'.$this->year->terms()->first()->getKey())
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_guardian_prints_published_rapor_only(): void
    {
        $service = app(ReportCardService::class);
        $this->generateCard();
        $card = $this->student->reportCards()->firstOrFail();

        $guardianUser = User::factory()->create();
        $guardianUser->assignRole('wali_murid');
        Guardian::factory()->create([
            'student_id' => $this->student->getKey(),
            'user_id' => $guardianUser->getKey(),
        ]);

        $route = "/rapor/{$card->getKey()}/pdf";

        $this->actingAs($guardianUser)->get($route)->assertForbidden();

        $service->submit($card, $this->staffUser('wali_kelas'));
        $kepsek = $this->staffUser('kepala_sekolah');
        $service->approve($card, $kepsek);
        $service->publish($card, $kepsek);

        $this->actingAs($guardianUser)
            ->get($route)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_guru_outside_the_classroom_cannot_print(): void
    {
        $this->generateCard();
        $outsider = $this->staffUser('guru');
        $card = $this->student->reportCards()->firstOrFail();

        $this->actingAs($outsider)->get("/rapor/{$card->getKey()}/pdf")->assertForbidden();
    }
}
