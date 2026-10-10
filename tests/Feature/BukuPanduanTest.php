<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Manual\ManualBook;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BukuPanduanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/buku-panduan')->assertRedirect('/admin/login');
    }

    public function test_staff_roles_open_the_manual_and_parents_are_kept_on_their_panel(): void
    {
        foreach (['super_admin', 'guru'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);

            $this->actingAs($user)
                ->get('/admin/buku-panduan')
                ->assertOk()
                ->assertSee('Buku Panduan');
        }

        $parent = User::factory()->create();
        $parent->assignRole('wali_murid');

        $this->actingAs($parent)->get('/admin/buku-panduan')->assertForbidden();
    }

    public function test_every_chapter_renders_via_the_bab_query_parameter(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        foreach (ManualBook::slugs() as $slug) {
            $chapter = ManualBook::chapter($slug);

            $this->assertNotNull($chapter);

            $this->actingAs($admin)
                ->get('/admin/buku-panduan?bab='.$slug)
                ->assertOk()
                ->assertSee(e($chapter['title']), false);
        }
    }

    public function test_unknown_bab_falls_back_to_the_first_chapter(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $first = ManualBook::chapter((string) ManualBook::slugs()[0]);
        $this->assertNotNull($first);

        $this->actingAs($admin)
            ->get('/admin/buku-panduan?bab=tidak-ada')
            ->assertOk()
            ->assertSee(e($first['title']), false);
    }

    public function test_page_exposes_heading_anchors_and_the_search_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)->get('/admin/buku-panduan');

        $response->assertOk();
        $response->assertSee('<h2 id="', false);
        $response->assertSee('x-ref="index"', false);
        $response->assertSee('manual-book__nav', false);
    }

    public function test_search_index_covers_every_chapter(): void
    {
        $index = collect(ManualBook::searchIndex());

        $this->assertEqualsCanonicalizing(ManualBook::slugs(), $index->pluck('bab')->unique()->all());

        $this->assertTrue($index->every(
            fn (array $entry): bool => filled($entry['babTitle']) && filled($entry['heading']) && is_string($entry['anchor']),
        ));
    }
}
