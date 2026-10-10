<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Asset;
use App\Models\Fund;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FundSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
            FundSeeder::class,
            AccountSeeder::class,
        ]);

        AcademicYear::factory()->forStartYear(2026)->create();
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        return $admin;
    }

    public function test_super_admin_can_render_asset_registry_pages(): void
    {
        $admin = $this->superAdmin();

        $asset = Asset::factory()->create([
            'fund_id' => Fund::query()->firstOrFail()->getKey(),
        ]);

        foreach ([
            '/admin/assets',
            '/admin/assets/'.$asset->getKey(),
            '/admin/asset-categories',
            '/admin/locations',
            '/admin/asset-maintenances',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_operator_tu_can_render_asset_pages(): void
    {
        $operatorTu = User::factory()->create();
        $operatorTu->assignRole('operator_tu');

        foreach ([
            '/admin/assets',
            '/admin/asset-categories',
            '/admin/locations',
            '/admin/asset-maintenances',
        ] as $url) {
            $this->actingAs($operatorTu)->get($url)->assertOk();
        }
    }

    public function test_guru_cannot_access_asset_pages(): void
    {
        $guru = User::factory()->create();
        $guru->assignRole('guru');

        foreach ([
            '/admin/assets',
            '/admin/asset-categories',
            '/admin/locations',
            '/admin/asset-maintenances',
        ] as $url) {
            $this->actingAs($guru)->get($url)->assertForbidden();
        }
    }
}
