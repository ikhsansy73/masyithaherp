<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\User;
use App\Services\Accounting\JournalDraft;
use App\Services\Accounting\JournalDraftLine;
use App\Services\Accounting\JournalPostingService;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FundSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LabaRugiPageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class, FundSeeder::class, AccountSeeder::class]);

        AcademicYear::factory()->forStartYear(2026)->create();

        $this->user = User::factory()->create();
        $this->user->assignRole('bendahara');
    }

    private function account(string $code): Account
    {
        return Account::query()->where('code', $code)->firstOrFail();
    }

    public function test_report_renders_revenue_expense_groups_and_surplus(): void
    {
        $journals = app(JournalPostingService::class);

        $journals->post(new JournalDraft(
            entryDate: Carbon::today(),
            description: 'SPP Oktober',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1100')->id, 1_250_000),
                JournalDraftLine::credit($this->account('4-1100')->id, 1_250_000),
            ],
        ));

        $journals->post(new JournalDraft(
            entryDate: Carbon::today(),
            description: 'Gaji guru',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('5-1110')->id, 500_000),
                JournalDraftLine::credit($this->account('1-1100')->id, 500_000),
            ],
        ));

        $this->actingAs($this->user)
            ->get('/admin/laba-rugi')
            ->assertOk()
            ->assertSee('PENDAPATAN')
            ->assertSee('Pendapatan SPP')
            ->assertSee('1.250.000')
            ->assertSee('BEBAN')
            ->assertSee('Beban Gaji Pokok')
            ->assertSee('500.000')
            ->assertSee('Total Pendapatan')
            ->assertSee('Total Beban')
            ->assertSee('Surplus / (Defisit)')
            ->assertSee('750.000');
    }

    public function test_report_renders_when_no_transactions(): void
    {
        $this->actingAs($this->user)
            ->get('/admin/laba-rugi')
            ->assertOk()
            ->assertSee('Surplus / (Defisit)');
    }
}
