<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Filament\Pages\AbsensiSiswa;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AbsensiSiswaPageTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Classroom $classroom;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('wali_kelas');

        $year = AcademicYear::factory()->create();
        $this->classroom = Classroom::factory()->grade(1)->create([
            'academic_year_id' => $year->getKey(),
        ]);

        $this->student = Student::factory()->create(['full_name' => 'Aisyah Putri']);
        $this->student->enrollments()->create([
            'academic_year_id' => $year->getKey(),
            'classroom_id' => $this->classroom->getKey(),
            'grade_level' => $this->classroom->grade_level,
            'status' => EnrollmentStatus::Aktif,
        ]);
    }

    public function test_page_loads_and_lists_roster_after_picking_classroom(): void
    {
        Livewire::actingAs($this->teacher)
            ->test(AbsensiSiswa::class)
            ->assertOk()
            ->set('data.classroomId', $this->classroom->getKey())
            ->call('loadRoster')
            ->assertSee('Aisyah Putri')
            ->assertSee('Tandai semua Hadir')
            ->assertSee('Simpan Absensi');
    }

    public function test_grid_defaults_every_student_to_hadir(): void
    {
        Livewire::actingAs($this->teacher)
            ->test(AbsensiSiswa::class)
            ->set('data.classroomId', $this->classroom->getKey())
            ->call('loadRoster')
            ->assertSet('statuses.'.$this->student->getKey(), 'hadir');
    }

    public function test_set_status_records_a_different_status(): void
    {
        Livewire::actingAs($this->teacher)
            ->test(AbsensiSiswa::class)
            ->set('data.classroomId', $this->classroom->getKey())
            ->call('loadRoster')
            ->call('setStatus', $this->student->getKey(), 'sakit')
            ->assertSet('statuses.'.$this->student->getKey(), 'sakit')
            // An unknown status value must be ignored.
            ->call('setStatus', $this->student->getKey(), 'terlambat')
            ->assertSet('statuses.'.$this->student->getKey(), 'sakit');
    }

    public function test_semua_hadir_resets_every_status(): void
    {
        Livewire::actingAs($this->teacher)
            ->test(AbsensiSiswa::class)
            ->set('data.classroomId', $this->classroom->getKey())
            ->call('loadRoster')
            ->call('setStatus', $this->student->getKey(), 'alpa')
            ->call('semuaHadir')
            ->assertSet('statuses.'.$this->student->getKey(), 'hadir');
    }

    public function test_saving_persists_attendance_for_the_whole_class(): void
    {
        Livewire::actingAs($this->teacher)
            ->test(AbsensiSiswa::class)
            ->set('data.classroomId', $this->classroom->getKey())
            ->call('loadRoster')
            ->call('setStatus', $this->student->getKey(), 'izin')
            ->call('simpan');

        $this->assertDatabaseHas('student_attendances', [
            'student_id' => $this->student->getKey(),
            'classroom_id' => $this->classroom->getKey(),
            'date' => today()->toDateString(),
            'status' => 'izin',
        ]);
    }

    public function test_reloading_a_saved_day_edits_instead_of_resetting(): void
    {
        StudentAttendance::query()->create([
            'student_id' => $this->student->getKey(),
            'classroom_id' => $this->classroom->getKey(),
            'date' => today()->toDateString(),
            'status' => 'alpa',
            'recorded_by' => $this->teacher->getKey(),
        ]);

        Livewire::actingAs($this->teacher)
            ->test(AbsensiSiswa::class)
            ->set('data.classroomId', $this->classroom->getKey())
            ->call('loadRoster')
            ->assertSet('statuses.'.$this->student->getKey(), 'alpa');
    }

    public function test_save_without_classroom_writes_nothing(): void
    {
        Livewire::actingAs($this->teacher)
            ->test(AbsensiSiswa::class)
            ->call('simpan');

        $this->assertDatabaseCount('student_attendances', 0);
    }

    public function test_user_without_create_permission_cannot_save(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('kepala_sekolah');

        Livewire::actingAs($viewer)
            ->test(AbsensiSiswa::class)
            ->set('data.classroomId', $this->classroom->getKey())
            ->call('loadRoster')
            ->call('simpan');

        $this->assertDatabaseCount('student_attendances', 0);
    }
}
