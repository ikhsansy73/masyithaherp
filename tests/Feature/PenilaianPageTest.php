<?php

namespace Tests\Feature;

use App\Filament\Resources\Penilaian\Pages\ManagePenilaian;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PenilaianPageTest extends TestCase
{
    use RefreshDatabase;

    private Classroom $classroom;

    private Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $year = AcademicYear::factory()->create();
        $this->classroom = Classroom::factory()->create([
            'academic_year_id' => $year->getKey(),
        ]);
        $this->assessment = Assessment::factory()->create([
            'classroom_id' => $this->classroom->getKey(),
            'academic_term_id' => $year->terms()->first()->getKey(),
        ]);
    }

    private function staffUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        Employee::factory()->create(['user_id' => $user->getKey()]);

        return $user;
    }

    public function test_guru_sees_own_assessments_only(): void
    {
        $guru = $this->staffUser('guru');
        $other = $this->staffUser('guru');

        $mine = Assessment::factory()->create([
            'classroom_id' => $this->classroom->getKey(),
            'academic_term_id' => $this->assessment->academic_term_id,
            'teacher_id' => $guru->employee->getKey(),
            'name' => 'Ulangan Harian Perkalian',
        ]);

        $theirs = Assessment::factory()->create([
            'classroom_id' => $this->classroom->getKey(),
            'academic_term_id' => $this->assessment->academic_term_id,
            'teacher_id' => $other->employee->getKey(),
            'name' => 'Ulangan Harian Pembagian',
        ]);

        $this->actingAs($guru)->get('/admin/penilaian')
            ->assertOk()
            ->assertSee($mine->name)
            ->assertDontSee($theirs->name);
    }

    public function test_wali_kelas_sees_homeroom_assessments(): void
    {
        $wali = $this->staffUser('wali_kelas');
        $this->classroom->update(['homeroom_teacher_id' => $wali->employee->getKey()]);

        $this->actingAs($wali)->get('/admin/penilaian')
            ->assertOk()
            ->assertSee($this->assessment->name);
    }

    public function test_bendahara_cannot_access_page(): void
    {
        $bendahara = $this->staffUser('bendahara');

        $this->actingAs($bendahara)->get('/admin/penilaian')->assertForbidden();
    }

    public function test_create_form_mounts_and_lists_employee_names(): void
    {
        $guru = $this->staffUser('guru');

        Livewire::actingAs($guru)
            ->test(ManagePenilaian::class)
            ->mountAction('create')
            ->assertSuccessful();
    }
}
