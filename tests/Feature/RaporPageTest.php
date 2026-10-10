<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Classroom;
use App\Models\ClassSubjectTeacher;
use App\Models\Employee;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academics\ReportCardService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Render smoke for the Rapor resource pages and row scoping. */
class RaporPageTest extends TestCase
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

    /**
     * One scored assessment so setUp data is realistic for all tests.
     */
    private function makeScoredAssessment(): void
    {
        $assessment = Assessment::factory()->create([
            'classroom_id' => $this->classroom->getKey(),
            'academic_term_id' => $this->year->terms()->first()->getKey(),
        ]);
        AssessmentScore::query()->create([
            'assessment_id' => $assessment->getKey(),
            'student_id' => $this->student->getKey(),
            'score' => 82,
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

    public function test_kepala_sekolah_sees_generated_cards(): void
    {
        $this->generateCard();
        $kepsek = $this->staffUser('kepala_sekolah');

        $this->actingAs($kepsek)->get('/admin/rapor')
            ->assertOk()
            ->assertSee($this->student->full_name);
    }

    public function test_wali_kelas_sees_own_classroom_cards(): void
    {
        $this->generateCard();
        $wali = $this->staffUser('wali_kelas');
        $this->classroom->update(['homeroom_teacher_id' => $wali->employee->getKey()]);

        $this->actingAs($wali)->get('/admin/rapor')
            ->assertOk()
            ->assertSee($this->student->full_name);
    }

    public function test_guru_sees_taught_classroom_cards(): void
    {
        $guru = $this->staffUser('guru');
        ClassSubjectTeacher::factory()->create([
            'classroom_id' => $this->classroom->getKey(),
            'teacher_id' => $guru->employee->getKey(),
        ]);
        $this->generateCard();

        $this->actingAs($guru)->get('/admin/rapor')
            ->assertOk()
            ->assertSee($this->student->full_name);
    }

    public function test_bendahara_cannot_access_page(): void
    {
        $bendahara = $this->staffUser('bendahara');

        $this->actingAs($bendahara)->get('/admin/rapor')->assertForbidden();
    }
}
