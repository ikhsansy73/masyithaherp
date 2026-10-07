<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PortalParentScopingTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    private Student $ownChild;

    private Student $otherChild;

    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parent = User::factory()->create();
        $this->parent->assignRole(Role::findOrCreate('wali_murid', 'web'));

        $year = AcademicYear::factory()->create();
        $this->classroom = Classroom::factory()->grade(1)->create([
            'academic_year_id' => $year->getKey(),
        ]);

        $this->ownChild = Student::factory()->create(['full_name' => 'Anak Sendiri']);
        $this->ownChild->guardians()->create([
            'relationship' => 'ayah',
            'name' => 'Ayah Anak Sendiri',
            'phone' => '081111111111',
            'is_primary_contact' => true,
            'user_id' => $this->parent->getKey(),
        ]);
        $this->ownChild->enrollments()->create([
            'academic_year_id' => $year->getKey(),
            'classroom_id' => $this->classroom->getKey(),
            'grade_level' => 1,
            'status' => 'aktif',
        ]);

        $this->otherChild = Student::factory()->create(['full_name' => 'Anak Orang Lain']);
        $this->otherChild->enrollments()->create([
            'academic_year_id' => $year->getKey(),
            'classroom_id' => $this->classroom->getKey(),
            'grade_level' => 1,
            'status' => 'aktif',
        ]);
    }

    public function test_parent_can_view_own_child(): void
    {
        $this->assertTrue($this->parent->can('view', $this->ownChild));
    }

    public function test_parent_cannot_view_other_children(): void
    {
        $this->assertFalse($this->parent->can('view', $this->otherChild));
    }

    public function test_parent_without_guardian_link_cannot_view_anyone(): void
    {
        $linkedParent = User::factory()->create();
        $linkedParent->assignRole(Role::findOrCreate('wali_murid', 'web'));

        $this->assertFalse($linkedParent->can('view', $this->ownChild));
        $this->assertFalse($linkedParent->can('view', $this->otherChild));
    }

    public function test_staff_with_permission_sees_all_students(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole(Role::findOrCreate('operator_tu', 'web'));
        \Spatie\Permission\Models\Permission::findOrCreate('students.student.view', 'web');
        \Spatie\Permission\Models\Permission::findOrCreate('students.student.viewAny', 'web');
        $operator->givePermissionTo(['students.student.view', 'students.student.viewAny']);

        $this->assertTrue($operator->can('view', $this->ownChild));
        $this->assertTrue($operator->can('view', $this->otherChild));
    }

    public function test_scope_visible_to_staff_limits_guru_to_own_classrooms(): void
    {
        $guru = User::factory()->create();
        $guru->assignRole(Role::findOrCreate('guru', 'web'));

        $employee = \App\Models\Employee::factory()->create([
            'user_id' => $guru->getKey(),
            'is_teaching' => true,
        ]);
        $this->classroom->update(['homeroom_teacher_id' => $employee->getKey()]);

        $otherClassroom = Classroom::factory()->grade(2)->create([
            'academic_year_id' => $this->classroom->academic_year_id,
        ]);

        $visible = \App\Models\Classroom::query()->visibleToStaff($guru)->pluck('id');

        $this->assertTrue($visible->contains($this->classroom->getKey()));
        $this->assertFalse($visible->contains($otherClassroom->getKey()));
    }

    public function test_scope_visible_to_staff_bypasses_for_admin_roles(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole(Role::findOrCreate('operator_tu', 'web'));

        $visible = \App\Models\Classroom::query()->visibleToStaff($operator)->pluck('id');

        $this->assertTrue($visible->contains($this->classroom->getKey()));
    }
}
