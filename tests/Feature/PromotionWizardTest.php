<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Exceptions\SchoolException;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Services\School\StudentMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PromotionWizardTest extends TestCase
{
    use RefreshDatabase;

    private StudentMovementService $service;

    private User $actor;

    private AcademicYear $sourceYear;

    private AcademicYear $targetYear;

    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(StudentMovementService::class);
        $this->actor = User::factory()->create();
        $this->sourceYear = AcademicYear::factory()->create([
            'name' => '2030/2031',
            'starts_at' => '2030-07-01',
            'ends_at' => '2031-06-30',
        ]);
        $this->targetYear = AcademicYear::factory()->create([
            'name' => '2031/2032',
            'starts_at' => '2031-07-01',
            'ends_at' => '2032-06-30',
        ]);
        $this->classroom = Classroom::factory()->grade(3, 'A')->create([
            'academic_year_id' => $this->sourceYear->getKey(),
        ]);
    }

    /**
     * Enroll a fresh student into the source rombel and return it.
     */
    private function enroll(string $name): Student
    {
        $student = Student::factory()->create(['full_name' => $name]);
        $student->enrollments()->create([
            'academic_year_id' => $this->sourceYear->getKey(),
            'classroom_id' => $this->classroom->getKey(),
            'grade_level' => 3,
            'status' => EnrollmentStatus::Aktif,
        ]);

        return $student;
    }

    public function test_naik_promotes_into_next_grade_of_target_year(): void
    {
        $student = $this->enroll('Budi');

        $moved = $this->service->promoteClassroom(
            from: $this->classroom,
            targetYear: $this->targetYear,
            decisions: [$student->getKey() => 'naik'],
            actor: $this->actor,
            movementDate: Carbon::parse('2031-06-20'),
        );

        $this->assertSame(1, $moved);

        $targetEnrollment = $student->enrollments()
            ->where('academic_year_id', $this->targetYear->getKey())
            ->sole();
        $this->assertSame(4, $targetEnrollment->grade_level);
        $this->assertSame(EnrollmentStatus::Aktif, $targetEnrollment->status);
        $this->assertSame('4A', $targetEnrollment->classroom->name);
        $this->assertSame('B', $targetEnrollment->classroom->fase);

        $this->assertDatabaseHas('student_movements', [
            'student_id' => $student->getKey(),
            'type' => 'kenaikan',
            'from_classroom_id' => $this->classroom->getKey(),
            'academic_year_id' => $this->sourceYear->getKey(),
        ]);
    }

    public function test_tinggal_kelas_repeats_same_grade(): void
    {
        $student = $this->enroll('Citra');

        $this->service->promoteClassroom(
            from: $this->classroom,
            targetYear: $this->targetYear,
            decisions: [$student->getKey() => 'tinggal_kelas'],
            actor: $this->actor,
            movementDate: Carbon::parse('2031-06-20'),
        );

        $targetEnrollment = $student->enrollments()
            ->where('academic_year_id', $this->targetYear->getKey())
            ->sole();
        $this->assertSame(3, $targetEnrollment->grade_level);
        $this->assertSame('3A', $targetEnrollment->classroom->name);

        $this->assertDatabaseHas('student_movements', [
            'student_id' => $student->getKey(),
            'type' => 'tinggal_kelas',
        ]);
    }

    public function test_lulus_closes_enrollment_and_marks_student_graduated(): void
    {
        $student = Student::factory()->create(['full_name' => 'Dewi']);
        $student->enrollments()->create([
            'academic_year_id' => $this->sourceYear->getKey(),
            'classroom_id' => $this->classroom->getKey(),
            'grade_level' => 6,
            'status' => EnrollmentStatus::Aktif,
        ]);

        $this->service->promoteClassroom(
            from: $this->classroom,
            targetYear: $this->targetYear,
            decisions: [$student->getKey() => 'lulus'],
            actor: $this->actor,
            movementDate: Carbon::parse('2031-06-20'),
        );

        $this->assertSame(EnrollmentStatus::Lulus, $student->enrollments()->sole()->status);
        $this->assertSame(StudentStatus::Lulus, $student->fresh()->status);
        $this->assertDatabaseHas('student_movements', [
            'student_id' => $student->getKey(),
            'type' => 'lulus',
            'to_classroom_id' => null,
        ]);
    }

    public function test_mutasi_keluar_and_keluar_record_exit(): void
    {
        $mutasi = $this->enroll('Eko');
        $keluar = $this->enroll('Fajar');

        $this->service->promoteClassroom(
            from: $this->classroom,
            targetYear: $this->targetYear,
            decisions: [
                $mutasi->getKey() => 'mutasi_keluar',
                $keluar->getKey() => 'keluar',
            ],
            actor: $this->actor,
            movementDate: Carbon::parse('2031-06-20'),
            notes: 'Pindah domisili',
        );

        $this->assertSame(EnrollmentStatus::Pindah, $mutasi->enrollments()->sole()->status);
        $this->assertSame(StudentStatus::MutasiKeluar, $mutasi->fresh()->status);
        $this->assertNotNull($mutasi->fresh()->exit_date);

        $this->assertSame(EnrollmentStatus::Keluar, $keluar->enrollments()->sole()->status);
        $this->assertSame(StudentStatus::Keluar, $keluar->fresh()->status);
        $this->assertSame('Pindah domisili', $keluar->fresh()->exit_reason);
    }

    public function test_naik_grade_6_is_rejected(): void
    {
        $student = Student::factory()->create();
        $student->enrollments()->create([
            'academic_year_id' => $this->sourceYear->getKey(),
            'classroom_id' => $this->classroom->getKey(),
            'grade_level' => 6,
            'status' => EnrollmentStatus::Aktif,
        ]);

        $this->expectException(SchoolException::class);
        $this->expectExceptionMessage('kelas 6');

        $this->service->promoteClassroom(
            from: $this->classroom,
            targetYear: $this->targetYear,
            decisions: [$student->getKey() => 'naik'],
            actor: $this->actor,
            movementDate: Carbon::parse('2031-06-20'),
        );
    }

    public function test_lulus_below_grade_6_is_rejected(): void
    {
        $student = $this->enroll('Gita');

        $this->expectException(SchoolException::class);
        $this->expectExceptionMessage('kelas 6');

        $this->service->promoteClassroom(
            from: $this->classroom,
            targetYear: $this->targetYear,
            decisions: [$student->getKey() => 'lulus'],
            actor: $this->actor,
            movementDate: Carbon::parse('2031-06-20'),
        );
    }

    public function test_target_year_must_be_after_source_year(): void
    {
        $student = $this->enroll('Hana');

        $this->expectException(SchoolException::class);
        $this->expectExceptionMessage('Tahun ajaran target harus setelah tahun ajaran rombel asal.');

        $this->service->promoteClassroom(
            from: $this->classroom,
            targetYear: $this->sourceYear,
            decisions: [$student->getKey() => 'naik'],
            actor: $this->actor,
            movementDate: Carbon::parse('2031-06-20'),
        );
    }

    public function test_already_enrolled_in_target_year_is_rejected(): void
    {
        $student = $this->enroll('Indra');
        $student->enrollments()->create([
            'academic_year_id' => $this->targetYear->getKey(),
            'classroom_id' => Classroom::factory()->grade(4)->create([
                'academic_year_id' => $this->targetYear->getKey(),
            ])->getKey(),
            'grade_level' => 4,
            'status' => EnrollmentStatus::Aktif,
        ]);

        $this->expectException(SchoolException::class);
        $this->expectExceptionMessage('sudah terdaftar di tahun ajaran target');

        $this->service->promoteClassroom(
            from: $this->classroom,
            targetYear: $this->targetYear,
            decisions: [$student->getKey() => 'naik'],
            actor: $this->actor,
            movementDate: Carbon::parse('2031-06-20'),
        );
    }

    public function test_move_student_handles_mid_year_exit(): void
    {
        $student = $this->enroll('Joko');

        $this->service->moveStudent(
            $student,
            'mutasi_keluar',
            $this->actor,
            Carbon::parse('2030-10-15'),
            'Pindah sekolah',
        );

        $this->assertSame(StudentStatus::MutasiKeluar, $student->fresh()->status);
        $this->assertSame('2030-10-15', $student->fresh()->exit_date->toDateString());
        $this->assertDatabaseHas('student_movements', [
            'student_id' => $student->getKey(),
            'type' => 'mutasi_keluar',
        ]);
    }

    public function test_move_student_rejects_naik(): void
    {
        $student = $this->enroll('Kartika');

        $this->expectException(SchoolException::class);
        $this->expectExceptionMessage('Keputusan harus mutasi_keluar atau keluar.');

        $this->service->moveStudent($student, 'naik', $this->actor, Carbon::parse('2030-10-15'));
    }
}
