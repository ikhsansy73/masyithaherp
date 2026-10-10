<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PeranIzinPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_roles_and_permission_chips(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->get('/admin/peran-izin')
            ->assertOk()
            ->assertSee('Daftar Peran')
            ->assertSee('izin terdaftar')
            ->assertSee('Super Admin')
            ->assertSee('Bendahara')
            ->assertSee('Wali Murid')
            ->assertSee('billing.arrears.view')
            ->assertSee('Jumlah Izin');
    }

    public function test_role_card_badge_shows_permission_count(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $count = Role::query()
            ->where('name', 'bendahara')
            ->withCount('permissions')
            ->first()
            ->permissions_count;

        $this->assertGreaterThan(0, $count);

        $this->actingAs($admin)
            ->get('/admin/peran-izin')
            ->assertOk()
            ->assertSee($count.' izin');
    }
}
