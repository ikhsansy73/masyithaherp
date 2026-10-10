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

class NeracaSaldoPageTest extends TestCase
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

    public function test_report_table_renders_posted_rows_and_totals(): void
    {
        app(JournalPostingService::class)->post(new JournalDraft(
            entryDate: Carbon::today(),
            description: 'SPP Oktober',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1100')->id, 1_250_000),
                JournalDraftLine::credit($this->account('4-1900')->id, 1_250_000),
            ],
        ));

        $this->actingAs($this->user)
            ->get('/admin/neraca-saldo')
            ->assertOk()
            ->assertSee('1-1100')
            ->assertSee('1.250.000')
            ->assertSee('Saldo Awal')
            ->assertSee('Saldo Akhir')
            ->assertSee('TOTAL');
    }

    public function test_empty_period_renders_placeholder_row(): void
    {
        $this->actingAs($this->user)
            ->get('/admin/neraca-saldo')
            ->assertOk()
            ->assertSee('Tidak ada transaksi pada periode ini.');
    }
}
