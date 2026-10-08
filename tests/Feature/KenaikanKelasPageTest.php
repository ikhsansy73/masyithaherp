<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Filament\Pages\KenaikanKelas;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KenaikanKelasPageTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private AcademicYear $sourceYear;

    private AcademicYear $targetYear;

    private Classroom $classroom;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $this->operator = User::factory()->create();
        $this->operator->assignRole('operator_tu');

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

        $this->student = Student::factory()->create(['full_name' => 'Aisyah Putri']);
        $this->student->enrollments()->create([
            'academic_year_id' => $this->sourceYear->getKey(),
            'classroom_id' => $this->classroom->getKey(),
            'grade_level' => 3,
            'status' => EnrollmentStatus::Aktif,
        ]);
    }

    public function test_page_loads_and_lists_roster_after_picking_source_and_target(): void
    {
        Livewire::actingAs($this->operator)
            ->test(KenaikanKelas::class)
            ->assertOk()
            ->set('data.classroomId', $this->classroom->getKey())
            ->set('data.targetYearId', $this->targetYear->getKey())
            ->assertSee('Aisyah Putri')
            ->assertSee('Semua Naik Kelas')
            ->assertSee('Proses Kenaikan Kelas');
    }

    public function test_grid_defaults_every_student_to_naik(): void
    {
        Livewire::actingAs($this->operator)
            ->test(KenaikanKelas::class)
            ->set('data.classroomId', $this->classroom->getKey())
            ->set('data.targetYearId', $this->targetYear->getKey())
            ->assertSet('decisions.'.$this->student->getKey(), 'naik');
    }

    public function test_changing_source_rombel_resets_target_year(): void
    {
        Livewire::actingAs($this->operator)
            ->test(KenaikanKelas::class)
            ->set('data.classroomId', $this->classroom->getKey())
            ->set('data.targetYearId', $this->targetYear->getKey())
            ->set('data.classroomId', $this->classroom->getKey())
            ->assertSet('data.targetYearId', null);
    }

    public function test_set_decision_records_choices_and_ignores_unknown(): void
    {
        Livewire::actingAs($this->operator)
            ->test(KenaikanKelas::class)
            ->set('data.classroomId', $this->classroom->getKey())
            ->set('data.targetYearId', $this->targetYear->getKey())
            ->call('setDecision', $this->student->getKey(), 'tinggal_kelas')
            ->assertSet('decisions.'.$this->student->getKey(), 'tinggal_kelas')
            ->call('setDecision', $this->student->getKey(), 'lulus_paksaa')
            ->assertSet('decisions.'.$this->student->getKey(), 'tinggal_kelas');
    }

    public function test_semua_naik_resets_every_decision(): void
    {
        Livewire::actingAs($this->operator)
            ->test(KenaikanKelas::class)
            ->set('data.classroomId', $this->classroom->getKey())
            ->set('data.targetYearId', $this->targetYear->getKey())
            ->call('setDecision', $this->student->getKey(), 'keluar')
            ->call('semuaNaik')
            ->assertSet('decisions.'.$this->student->getKey(), 'naik');
    }

    public function test_saving_runs_the_promotion_for_the_whole_class(): void
    {
        Livewire::actingAs($this->operator)
            ->test(KenaikanKelas::class)
            ->set('data.classroomId', $this->classroom->getKey())
            ->set('data.targetYearId', $this->targetYear->getKey())
            ->call('setDecision', $this->student->getKey(), 'naik')
            ->call('simpan');

        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $this->student->getKey(),
            'academic_year_id' => $this->targetYear->getKey(),
            'grade_level' => 4,
            'status' => 'aktif',
        ]);

        $this->assertDatabaseHas('student_movements', [
            'student_id' => $this->student->getKey(),
            'type' => 'kenaikan',
            'from_classroom_id' => $this->classroom->getKey(),
        ]);
    }

    public function test_save_without_picks_writes_nothing(): void
    {
        Livewire::actingAs($this->operator)
            ->test(KenaikanKelas::class)
            ->call('simpan');

        $this->assertDatabaseCount('student_movements', 0);
    }

    public function test_user_without_update_permission_cannot_save(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('wali_kelas');

        Livewire::actingAs($viewer)
            ->test(KenaikanKelas::class)
            ->set('data.classroomId', $this->classroom->getKey())
            ->set('data.targetYearId', $this->targetYear->getKey())
            ->call('simpan');

        $this->assertDatabaseCount('student_movements', 0);
    }
}
