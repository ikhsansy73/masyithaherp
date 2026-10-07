<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_all_phase_one_admin_pages(): void
    {
        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        foreach (['/admin', '/admin/academic-years', '/admin/users', '/admin/peran-izin', '/admin/profil-sekolah'] as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk();
        }
    }

    public function test_bendahara_cannot_view_user_management(): void
    {
        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);

        $bendahara = User::factory()->create();
        $bendahara->assignRole('bendahara');

        $this->actingAs($bendahara)
            ->get('/admin/users')
            ->assertForbidden();

        $this->actingAs($bendahara)
            ->get('/admin/peran-izin')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }
}
