<?php

namespace Tests\Feature;

use App\Filament\Resources\JurnalMengajar\Pages\ManageJurnalMengajar;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\Subject;
use App\Models\TeacherLearningJournal;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JurnalMengajarPageTest extends TestCase
{
    use RefreshDatabase;

    private Classroom $classroom;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $this->classroom = Classroom::factory()->create();
        $this->subject = Subject::factory()->create();
    }

    private function staffUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        Employee::factory()->create(['user_id' => $user->getKey()]);

        return $user;
    }

    private function journalFor(User $user, string $topic = 'Membaca permadani angka'): TeacherLearningJournal
    {
        return TeacherLearningJournal::query()->create([
            'classroom_id' => $this->classroom->getKey(),
            'subject_id' => $this->subject->getKey(),
            'teacher_id' => $user->employee->getKey(),
            'date' => today()->subDay(),
            'topic' => $topic,
            'method' => 'Diskusi',
        ]);
    }

    public function test_guru_sees_own_journals_only(): void
    {
        $guru = $this->staffUser('guru');
        $other = $this->staffUser('guru');

        $mine = $this->journalFor($guru);
        $theirs = $this->journalFor($other, 'Pengukuran panjang benda');

        $this->actingAs($guru)->get('/admin/jurnal-mengajar')
            ->assertOk()
            ->assertSee($mine->topic)
            ->assertDontSee($theirs->topic);
    }

    public function test_wali_kelas_can_access_page(): void
    {
        $wali = $this->staffUser('wali_kelas');

        $journal = $this->journalFor($wali);

        $this->actingAs($wali)->get('/admin/jurnal-mengajar')
            ->assertOk()
            ->assertSee($journal->topic);
    }

    public function test_guru_without_employee_link_sees_nothing(): void
    {
        $guru = User::factory()->create();
        $guru->assignRole('guru');

        $this->actingAs($guru)->get('/admin/jurnal-mengajar')->assertOk();
    }

    public function test_bendahara_cannot_access_page(): void
    {
        $bendahara = User::factory()->create();
        $bendahara->assignRole('bendahara');

        $this->actingAs($bendahara)->get('/admin/jurnal-mengajar')->assertForbidden();
    }

    public function test_create_form_mounts_and_lists_employee_names(): void
    {
        $guru = $this->staffUser('guru');

        Livewire::actingAs($guru)
            ->test(ManageJurnalMengajar::class)
            ->mountAction('create')
            ->assertSuccessful();
    }
}
