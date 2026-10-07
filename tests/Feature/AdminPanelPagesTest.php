<?php

namespace Tests\Feature;

use App\Enums\PeriodStatus;
use App\Filament\Pages\PeriodeAkuntansi;
use App\Filament\Resources\Accounts\Pages\ManageAccounts;
use App\Filament\Resources\JournalEntries\Pages\ManageJournalEntries;
use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\User;
use App\Services\Accounting\JournalDraft;
use App\Services\Accounting\JournalDraftLine;
use App\Services\Accounting\JournalPostingService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    /**
     * A bendahara: full accounting CRUD plus report access.
     */
    private function accountant(): User
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $user = User::factory()->create();
        $user->assignRole('bendahara');

        return $user;
    }

    public function test_bendahara_can_view_phase_two_accounting_pages(): void
    {
        $user = $this->accountant();

        foreach ([
            '/admin/accounts',
            '/admin/journal-entries',
            '/admin/periode-akuntansi',
            '/admin/neraca-saldo',
            '/admin/buku-besar',
            '/admin/laba-rugi',
            '/admin/neraca',
            '/admin/arus-kas',
        ] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_kepala_sekolah_has_read_only_accounting_access(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $user = User::factory()->create();
        $user->assignRole('kepala_sekolah');

        foreach (['/admin/accounts', '/admin/journal-entries', '/admin/periode-akuntansi', '/admin/neraca-saldo', '/admin/neraca'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }

        Livewire::actingAs($user)->test(ManageAccounts::class)->assertActionHidden('create');
        Livewire::actingAs($user)->test(ManageJournalEntries::class)->assertActionHidden('create');
    }

    public function test_bendahara_can_post_manual_journal_from_the_panel(): void
    {
        $user = $this->accountant();
        $this->seed([\Database\Seeders\FundSeeder::class, \Database\Seeders\AccountSeeder::class]);
        AcademicYear::factory()->forStartYear(2026)->create();

        $kas = Account::query()->where('code', '1-1100')->firstOrFail();
        $spp = Account::query()->where('code', '4-1100')->firstOrFail();

        Livewire::actingAs($user)
            ->test(ManageJournalEntries::class)
            ->callAction('create', [
                'entry_date' => '2026-10-05',
                'description' => 'SPP tunai via panel',
                'lines' => [
                    ['account_id' => $kas->getKey(), 'fund_id' => null, 'debit' => 500000, 'credit' => 0, 'memo' => null],
                    ['account_id' => $spp->getKey(), 'fund_id' => null, 'debit' => 0, 'credit' => 500000, 'memo' => null],
                ],
            ]);

        $this->assertDatabaseHas('journal_entries', [
            'description' => 'SPP tunai via panel',
            'status' => 'posted',
            'source' => 'manual',
        ]);
        $this->assertDatabaseCount('journal_lines', 2);
    }

    public function test_panel_rejects_unbalanced_journal(): void
    {
        $user = $this->accountant();
        $this->seed([\Database\Seeders\FundSeeder::class, \Database\Seeders\AccountSeeder::class]);
        AcademicYear::factory()->forStartYear(2026)->create();

        $kas = Account::query()->where('code', '1-1100')->firstOrFail();
        $spp = Account::query()->where('code', '4-1100')->firstOrFail();

        Livewire::actingAs($user)
            ->test(ManageJournalEntries::class)
            ->callAction('create', [
                'entry_date' => '2026-10-05',
                'description' => 'Jurnal timpang',
                'lines' => [
                    ['account_id' => $kas->getKey(), 'fund_id' => null, 'debit' => 400000, 'credit' => 0, 'memo' => null],
                    ['account_id' => $spp->getKey(), 'fund_id' => null, 'debit' => 0, 'credit' => 300000, 'memo' => null],
                ],
            ])
            ->assertHasActionErrors();

        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_bendahara_can_void_journal_from_the_panel(): void
    {
        $user = $this->accountant();
        $this->seed([\Database\Seeders\FundSeeder::class, \Database\Seeders\AccountSeeder::class]);
        AcademicYear::factory()->forStartYear(2026)->create();

        $entry = app(JournalPostingService::class)->post(new JournalDraft(
            entryDate: \Illuminate\Support\Carbon::parse('2026-10-05'),
            description: 'Salah target',
            userId: $user->getKey(),
            lines: [
                JournalDraftLine::debit(Account::query()->where('code', '1-1100')->firstOrFail()->getKey(), 500_000),
                JournalDraftLine::credit(Account::query()->where('code', '4-1100')->firstOrFail()->getKey(), 500_000),
            ],
        ));

        Livewire::actingAs($user)
            ->test(ManageJournalEntries::class)
            ->callTableAction('void', $entry, ['reason' => 'Salah input']);

        $this->assertDatabaseHas('journal_entries', [
            'id' => $entry->getKey(),
            'status' => 'void',
        ]);
        $this->assertDatabaseCount('journal_entries', 2);
    }

    public function test_coa_create_only_accepts_expense_account_codes(): void
    {
        $user = $this->accountant();

        Livewire::actingAs($user)
            ->test(ManageAccounts::class)
            ->callAction('create', [
                'code' => '4-9999',
                'name' => 'Salah kelompok',
                'type' => 'beban',
                'normal_balance' => 'debit',
                'cash_flow_category' => 'operasi',
                'default_fund_id' => null,
                'is_active' => true,
            ])
            ->assertHasActionErrors(['code']);

        $this->assertDatabaseCount('accounts', 0);

        Livewire::actingAs($user)
            ->test(ManageAccounts::class)
            ->callAction('create', [
                'code' => '5-9100',
                'name' => 'Beban Listrik',
                'type' => 'beban',
                'normal_balance' => 'debit',
                'cash_flow_category' => 'operasi',
                'default_fund_id' => null,
                'is_active' => true,
            ]);

        $this->assertDatabaseHas('accounts', ['code' => '5-9100', 'name' => 'Beban Listrik']);
    }

    public function test_tutup_buku_action_closes_period_from_panel(): void
    {
        $user = $this->accountant();
        $this->seed([\Database\Seeders\FundSeeder::class, \Database\Seeders\AccountSeeder::class]);
        AcademicYear::factory()->forStartYear(2026)->create();

        app(JournalPostingService::class)->post(new JournalDraft(
            entryDate: \Illuminate\Support\Carbon::parse('2026-10-05'),
            description: 'SPP Oktober',
            userId: $user->getKey(),
            lines: [
                JournalDraftLine::debit(Account::query()->where('code', '1-1100')->firstOrFail()->getKey(), 400_000),
                JournalDraftLine::credit(Account::query()->where('code', '4-1100')->firstOrFail()->getKey(), 400_000),
            ],
        ));

        $period = AccountingPeriod::query()->where('name', '2026-10')->firstOrFail();

        Livewire::actingAs($user)
            ->test(PeriodeAkuntansi::class)
            ->assertTableActionVisible('tutup-buku', $period)
            ->callTableAction('tutup-buku', $period);

        $period->refresh();
        $this->assertSame(PeriodStatus::Closed, $period->status);
        $this->assertDatabaseHas('journal_entries', ['description' => 'Jurnal penutup Oktober 2026']);
    }

    public function test_only_super_admin_can_reopen_period_from_panel(): void
    {
        $user = $this->accountant();
        $this->seed([\Database\Seeders\FundSeeder::class, \Database\Seeders\AccountSeeder::class]);
        AcademicYear::factory()->forStartYear(2026)->create();

        $period = AccountingPeriod::query()->where('name', '2026-10')->firstOrFail();

        app(\App\Services\Accounting\AccountingPeriodService::class)->close($period, $user->getKey());

        Livewire::actingAs($user)
            ->test(PeriodeAkuntansi::class)
            ->assertTableActionHidden('buka-kembali', $period);

        $super = User::factory()->create();
        $super->assignRole('super_admin');

        Livewire::actingAs($super)
            ->test(PeriodeAkuntansi::class)
            ->assertTableActionVisible('buka-kembali', $period)
            ->callTableAction('buka-kembali', $period);

        $period->refresh();
        $this->assertSame(PeriodStatus::Open, $period->status);
    }
}
