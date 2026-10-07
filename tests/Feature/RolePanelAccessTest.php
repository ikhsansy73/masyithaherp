<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePanelAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Staff roles from the design (docs/design/09-auth-roles.md §1).
     *
     * @var list<string>
     */
    private const STAFF_ROLES = [
        'super_admin',
        'kepala_sekolah',
        'bendahara',
        'operator_tu',
        'guru',
        'wali_kelas',
    ];

    public function test_each_staff_role_can_access_admin_panel(): void
    {
        foreach (self::STAFF_ROLES as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');
            $user = User::factory()->create();
            $user->assignRole($role);

            $this->actingAs($user)
                ->get('/admin')
                ->assertOk();
        }
    }

    public function test_wali_murid_can_access_portal_panel(): void
    {
        $role = Role::findOrCreate('wali_murid', 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get('/portal')
            ->assertOk();
    }

    public function test_wali_murid_cannot_access_admin_panel(): void
    {
        $role = Role::findOrCreate('wali_murid', 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_staff_roles_cannot_access_portal_panel(): void
    {
        foreach (self::STAFF_ROLES as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');
            $user = User::factory()->create();
            $user->assignRole($role);

            $this->actingAs($user)
                ->get('/portal')
                ->assertForbidden();
        }
    }

    public function test_inactive_user_is_rejected_from_both_panels(): void
    {
        $adminRole = Role::findOrCreate('super_admin', 'web');
        $portalRole = Role::findOrCreate('wali_murid', 'web');

        $staff = User::factory()->inactive()->create();
        $staff->assignRole($adminRole);

        $parent = User::factory()->inactive()->create();
        $parent->assignRole($portalRole);

        $this->actingAs($staff)
            ->get('/admin')
            ->assertForbidden();

        $this->actingAs($parent)
            ->get('/portal')
            ->assertForbidden();
    }

    public function test_user_without_any_role_is_rejected_from_both_panels(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/portal')
            ->assertForbidden();
    }
}
