<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        return $admin;
    }

    public function test_super_admin_can_render_every_billing_page(): void
    {
        $admin = $this->superAdmin();

        foreach ([
            '/admin/cash-accounts',
            '/admin/fee-types',
            '/admin/fee-structures',
            '/admin/student-fees',
            '/admin/discounts',
            '/admin/invoice-batches',
            '/admin/invoices',
            '/admin/payments',
            '/admin/tunggakan',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_guru_role_only_sees_tunggakan(): void
    {
        $guru = User::factory()->create();
        $guru->assignRole('guru');

        foreach ([
            '/admin/cash-accounts',
            '/admin/fee-types',
            '/admin/fee-structures',
            '/admin/student-fees',
            '/admin/discounts',
            '/admin/invoice-batches',
            '/admin/invoices',
            '/admin/payments',
        ] as $url) {
            $this->actingAs($guru)->get($url)->assertForbidden();
        }

        $this->actingAs($guru)->get('/admin/tunggakan')->assertOk();
        $this->actingAs($guru)->get('/billing/daftar-tunggakan')->assertOk();
    }
}
