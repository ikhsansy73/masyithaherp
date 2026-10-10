<?php

namespace Tests\Feature;

use App\Models\LearningAchievement;
use App\Models\User;
use Database\Seeders\LearningAchievementSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KurikulumPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
            SubjectSeeder::class,
            LearningAchievementSeeder::class,
        ]);
    }

    public function test_kepala_sekolah_sees_kurikulum_page_and_cp_rows(): void
    {
        $kepala = User::factory()->create();
        $kepala->assignRole('kepala_sekolah');

        $this->actingAs($kepala)->get('/admin/kurikulum')
            ->assertOk()
            ->assertSee('Kurikulum (CP/TP)');

        $cp = LearningAchievement::query()->firstOrFail();
        $this->actingAs($kepala)->get('/admin/kurikulum/'.$cp->getKey())->assertOk();
    }

    public function test_guru_can_view_but_not_edit_kurikulum(): void
    {
        $guru = User::factory()->create();
        $guru->assignRole('guru');

        $page = $this->actingAs($guru)->get('/admin/kurikulum')->assertOk();

        $cp = LearningAchievement::query()->firstOrFail();
        $this->assertNotNull($cp);

        // guru holds academics.curriculum.viewAny but not academics.curriculum.create
        $this->assertFalse($guru->can('academics.curriculum.create'));
        $this->assertTrue($guru->can('academics.curriculum.viewAny'));
    }

    public function test_bendahara_cannot_access_kurikulum_page(): void
    {
        $bendahara = User::factory()->create();
        $bendahara->assignRole('bendahara');

        $this->actingAs($bendahara)->get('/admin/kurikulum')->assertForbidden();
    }
}
