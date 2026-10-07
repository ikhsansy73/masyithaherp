<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentAttendanceStatus;
use App\Exceptions\SchoolException;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\User;
use App\Services\School\StudentAttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceUniquenessTest extends TestCase
{
    use RefreshDatabase;

    private StudentAttendanceService $service;

    private User $recorder;

    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(StudentAttendanceService::class);
        $this->recorder = User::factory()->create();
        $year = AcademicYear::factory()->create();
        $this->classroom = Classroom::factory()->grade(2)->create([
            'academic_year_id' => $year->getKey(),
        ]);
    }

    /**
     * Enroll a student in the test classroom.
     */
    private function enroll(string $name): Student
    {
        $student = Student::factory()->create(['full_name' => $name]);
        $student->enrollments()->create([
            'academic_year_id' => $this->classroom->academic_year_id,
            'classroom_id' => $this->classroom->getKey(),
            'grade_level' => $this->classroom->grade_level,
            'status' => EnrollmentStatus::Aktif,
        ]);

        return $student;
    }

    public function test_saving_twice_updates_instead_of_duplicating(): void
    {
        $student = $this->enroll('Budi');
        $date = today()->subDay();

        $this->service->saveForClassroom($this->classroom, $date, [
            $student->getKey() => StudentAttendanceStatus::Hadir->value,
        ], $this->recorder);

        $this->service->saveForClassroom($this->classroom, $date, [
            $student->getKey() => StudentAttendanceStatus::Sakit->value,
        ], $this->recorder);

        $row = StudentAttendance::query()->sole();
        $this->assertSame(StudentAttendanceStatus::Sakit, $row->status);
        $this->assertSame($student->getKey(), $row->student_id);
    }

    public function test_future_date_is_rejected(): void
    {
        $student = $this->enroll('Citra');

        $this->expectException(SchoolException::class);
        $this->expectExceptionMessage('Tanggal absensi tidak boleh di masa depan.');

        $this->service->saveForClassroom($this->classroom, today()->addDay(), [
            $student->getKey() => StudentAttendanceStatus::Hadir->value,
        ], $this->recorder);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $student = $this->enroll('Dewi');

        $this->expectException(SchoolException::class);
        $this->expectExceptionMessage("Status kehadiran 'terlambat' tidak valid.");

        $this->service->saveForClassroom($this->classroom, today()->subDay(), [
            $student->getKey() => 'terlambat',
        ], $this->recorder);
    }

    public function test_whole_class_grid_writes_one_row_per_student(): void
    {
        $first = $this->enroll('Eko');
        $second = $this->enroll('Fajar');
        $date = today()->subDay();

        $written = $this->service->saveForClassroom($this->classroom, $date, [
            $first->getKey() => StudentAttendanceStatus::Hadir->value,
            $second->getKey() => StudentAttendanceStatus::Alpa->value,
        ], $this->recorder);

        $this->assertSame(2, $written);
        $this->assertSame(2, StudentAttendance::query()->count());
        $this->assertDatabaseHas('student_attendances', [
            'student_id' => $second->getKey(),
            'date' => $date->toDateString(),
            'status' => 'alpa',
            'classroom_id' => $this->classroom->getKey(),
        ]);
    }
}
