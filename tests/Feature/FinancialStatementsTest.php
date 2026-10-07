<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\User;
use App\Services\Accounting\JournalDraft;
use App\Services\Accounting\JournalDraftLine;
use App\Services\Accounting\JournalPostingService;
use App\Services\Accounting\Reports\BalanceSheet;
use App\Services\Accounting\Reports\GeneralLedger;
use App\Services\Accounting\Reports\IncomeStatement;
use App\Services\Accounting\Reports\TrialBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FinancialStatementsTest extends TestCase
{
    use RefreshDatabase;

    private JournalPostingService $journals;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([\Database\Seeders\FundSeeder::class, \Database\Seeders\AccountSeeder::class]);

        AcademicYear::factory()->forStartYear(2026)->create();

        $this->journals = app(JournalPostingService::class);
        $this->user = User::factory()->create();
    }

    private function account(string $code): Account
    {
        return Account::query()->where('code', $code)->firstOrFail();
    }

    public function test_balanced_manual_entry_appears_in_all_statements(): void
    {
        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-15'),
            description: 'Terima donasi tunai',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1100')->id, 1_000_000),
                JournalDraftLine::credit($this->account('4-1900')->id, 1_000_000),
            ],
        ));

        // Neraca Saldo: the entry shows as mutasi.
        $trialBalance = app(TrialBalance::class)->generate(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31')
        );

        $cashRow = collect($trialBalance['rows'])->firstWhere('code', '1-1100');

        $this->assertSame(1_000_000, $cashRow['mutasi_debit']);
        $this->assertSame(1_000_000, $trialBalance['totals']['mutasi_debit']);

        // Buku Besar: the line appears with a running balance.
        $ledger = app(GeneralLedger::class)->generate(
            $this->account('1-1100'),
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31')
        );

        $this->assertSame(1, count($ledger['lines']));
        $this->assertSame('Terima donasi tunai', $ledger['lines'][0]['description']);
        $this->assertSame(1_000_000, $ledger['lines'][0]['running']);
        $this->assertSame(1_000_000, $ledger['closing']);

        // Laba Rugi: revenue and surplus.
        $income = app(IncomeStatement::class)->generate(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31')
        );

        $this->assertSame(1_000_000, $income['revenue_total']);
        $this->assertSame(1_000_000, $income['surplus']);

        // Neraca: cash asset funded by current surplus; A = K + E asserted inside.
        $balance = app(BalanceSheet::class)->generate(Carbon::parse('2026-08-31'));

        $this->assertSame(1_000_000, $balance['assets_total']);
        $this->assertSame(0, $balance['liabilities_total']);
        $this->assertSame(1_000_000, $balance['surplus']);
        $this->assertSame(1_000_000, $balance['equity_total']);
    }

    public function test_general_ledger_running_balance_and_opening(): void
    {
        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-05'),
            description: 'Masuk pertama',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1100')->id, 400_000),
                JournalDraftLine::credit($this->account('4-1900')->id, 400_000),
            ],
        ));

        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-25'),
            description: 'Masuk kedua',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1100')->id, 600_000),
                JournalDraftLine::credit($this->account('4-1700')->id, 600_000),
            ],
        ));

        $ledger = app(GeneralLedger::class)->generate(
            $this->account('1-1100'),
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31')
        );

        $this->assertSame(0, $ledger['opening']);
        $this->assertSame(400_000, $ledger['lines'][0]['running']);
        $this->assertSame(1_000_000, $ledger['lines'][1]['running']);
        $this->assertSame(1_000_000, $ledger['closing']);

        // September carries the opening but has no lines.
        $september = app(GeneralLedger::class)->generate(
            $this->account('1-1100'),
            Carbon::parse('2026-09-01'),
            Carbon::parse('2026-09-30')
        );

        $this->assertSame(1_000_000, $september['opening']);
        $this->assertSame([], $september['lines']);
        $this->assertSame(1_000_000, $september['closing']);
    }

    public function test_income_statement_groups_and_fund_filter(): void
    {
        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-10'),
            description: 'DSP (komite)',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1100')->id, 500_000),
                JournalDraftLine::credit($this->account('4-1100')->id, 500_000),
            ],
        ));

        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-15'),
            description: 'Donasi & beban YYS',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1100')->id, 300_000),
                JournalDraftLine::credit($this->account('4-1900')->id, 300_000),
            ],
        ));

        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-20'),
            description: 'Beban gaji',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('5-1110')->id, 200_000),
                JournalDraftLine::credit($this->account('1-1100')->id, 200_000),
            ],
        ));

        $income = app(IncomeStatement::class)->generate(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31')
        );

        $this->assertSame(800_000, $income['revenue_total']);
        $this->assertSame(200_000, $income['expenses_total']);
        $this->assertSame(600_000, $income['surplus']);

        $yys = \App\Models\Fund::query()->where('code', 'YYS')->firstOrFail();

        $yysIncome = app(IncomeStatement::class)->generate(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31'),
            $yys->id
        );

        $this->assertSame(300_000, $yysIncome['revenue_total']);
        $this->assertSame(200_000, $yysIncome['expenses_total']);
        $this->assertSame(100_000, $yysIncome['surplus']);
    }

    public function test_balance_sheet_ties_after_tutup_buku(): void
    {
        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-10'),
            description: 'SPP',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1100')->id, 800_000),
                JournalDraftLine::credit($this->account('4-1900')->id, 800_000),
            ],
        ));

        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-20'),
            description: 'Beban ATK',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('5-1110')->id, 300_000),
                JournalDraftLine::credit($this->account('1-1100')->id, 300_000),
            ],
        ));

        // Tutup buku: revenue and expenses fold into 3-1200.
        app(\App\Services\Accounting\AccountingPeriodService::class)
            ->close(\App\Models\AccountingPeriod::query()->where('name', '2026-08')->firstOrFail(), $this->user->id);

        $balance = app(BalanceSheet::class)->generate(Carbon::parse('2026-08-31'));

        // Assets unchanged by the closing JE; equity now sits on 3-1200.
        $this->assertSame(500_000, $balance['assets_total']);
        $this->assertSame(500_000, $balance['equity_total']);
        $this->assertSame(0, $balance['surplus']);
    }
}
