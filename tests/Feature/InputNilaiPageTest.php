<?php

namespace Tests\Feature;

use App\Enums\LearningPredicate;
use App\Exceptions\SchoolException;
use App\Filament\Pages\InputNilai;
use App\Models\AcademicTerm;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\Student;
use App\Models\User;
use App\Services\Academics\AssessmentService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InputNilaiPageTest extends TestCase
{
    use RefreshDatabase;

    private Classroom $classroom;

    private AcademicTerm $term;

    private Assessment $assessment;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        // Years auto-seed their terms (AcademicYearService), so create the
        // year first and pick one of its terms instead of AcademicTerm::factory().
        $year = \App\Models\AcademicYear::factory()->create();
        $this->term = $year->terms()->first();

        $this->classroom = Classroom::factory()->create([
            'academic_year_id' => $year->getKey(),
        ]);

        $this->assessment = Assessment::factory()->create([
            'classroom_id' => $this->classroom->getKey(),
            'academic_term_id' => $this->term->getKey(),
            'max_score' => 100,
        ]);

        $this->student = Student::factory()->create();
        $this->student->enrollments()->create([
            'academic_year_id' => $this->classroom->academic_year_id,
            'classroom_id' => $this->classroom->getKey(),
            'grade_level' => $this->classroom->grade_level,
            'status' => 'aktif',
        ]);
    }

    private function guruUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('guru');
        $employee = Employee::factory()->create(['user_id' => $user->getKey()]);

        $this->assessment->forceFill(['teacher_id' => $employee->getKey()])->save();

        return $user;
    }

    public function test_input_page_renders_for_guru(): void
    {
        $guru = $this->guruUser();

        Livewire::actingAs($guru)
            ->test(InputNilai::class)
            ->assertSuccessful();
    }

    public function test_input_page_denied_for_bendahara(): void
    {
        $bendahara = User::factory()->create();
        $bendahara->assignRole('bendahara');

        $this->actingAs($bendahara)->get('/admin/input-nilai')->assertForbidden();
    }

    public function test_saving_scores_round_trip(): void
    {
        $guru = $this->guruUser();

        Livewire::actingAs($guru)
            ->test(InputNilai::class)
            ->set('data.classroomId', $this->classroom->getKey())
            ->set('data.academicTermId', $this->term->getKey())
            ->set('data.assessmentId', $this->assessment->getKey())
            ->call('loadScores')
            ->assertCount('scores', 1)
            ->set("scores.{$this->student->getKey()}.score", '85')
            ->call('simpan');

        $this->assertDatabaseHas('assessment_scores', [
            'assessment_id' => $this->assessment->getKey(),
            'student_id' => $this->student->getKey(),
            'score' => 85,
        ]);
    }

    public function test_service_rejects_score_and_predicate_together(): void
    {
        $guru = $this->guruUser();

        $this->expectException(SchoolException::class);
        $this->expectExceptionMessage('Isi salah satu saja: nilai angka atau predikat.');

        app(AssessmentService::class)->saveScores(
            assessment: $this->assessment,
            scores: [
                $this->student->getKey() => ['score' => 85, 'predicate' => 'BSH'],
            ],
            recorder: $guru,
        );
    }

    public function test_service_rejects_score_above_max(): void
    {
        $guru = $this->guruUser();

        $this->expectException(SchoolException::class);
        $this->expectExceptionMessage('melebihi nilai maksimal');

        app(AssessmentService::class)->saveScores(
            assessment: $this->assessment,
            scores: [
                $this->student->getKey() => ['score' => 120],
            ],
            recorder: $guru,
        );
    }

    public function test_service_rejects_unenrolled_student(): void
    {
        $guru = $this->guruUser();

        $outsider = Student::factory()->create();

        $this->expectException(SchoolException::class);
        $this->expectExceptionMessage('tidak terdaftar aktif');

        app(AssessmentService::class)->saveScores(
            assessment: $this->assessment,
            scores: [
                $outsider->getKey() => ['score' => 70],
            ],
            recorder: $guru,
        );
    }

    public function test_clearing_both_fields_deletes_the_score_row(): void
    {
        $guru = $this->guruUser();

        AssessmentScore::query()->create([
            'assessment_id' => $this->assessment->getKey(),
            'student_id' => $this->student->getKey(),
            'score' => 75,
        ]);

        app(AssessmentService::class)->saveScores(
            assessment: $this->assessment,
            scores: [
                $this->student->getKey() => ['score' => null, 'predicate' => null],
            ],
            recorder: $guru,
        );

        $this->assertDatabaseMissing('assessment_scores', [
            'assessment_id' => $this->assessment->getKey(),
            'student_id' => $this->student->getKey(),
        ]);
    }

    public function test_predicate_save_uses_rubric_value(): void
    {
        $guru = $this->guruUser();

        app(AssessmentService::class)->saveScores(
            assessment: $this->assessment,
            scores: [
                $this->student->getKey() => ['predicate' => LearningPredicate::SB->value],
            ],
            recorder: $guru,
        );

        $this->assertDatabaseHas('assessment_scores', [
            'assessment_id' => $this->assessment->getKey(),
            'student_id' => $this->student->getKey(),
            'predicate' => 'SB',
            'score' => null,
        ]);
    }
}
