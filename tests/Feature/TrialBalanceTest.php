<?php

namespace Tests\Feature;

use App\Exceptions\AccountingException;
use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\User;
use App\Services\Accounting\JournalDraft;
use App\Services\Accounting\JournalDraftLine;
use App\Services\Accounting\JournalPostingService;
use App\Services\Accounting\Reports\TrialBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TrialBalanceTest extends TestCase
{
    use RefreshDatabase;

    private JournalPostingService $journals;

    private TrialBalance $report;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([\Database\Seeders\FundSeeder::class, \Database\Seeders\AccountSeeder::class]);

        AcademicYear::factory()->forStartYear(2026)->create();

        $this->journals = app(JournalPostingService::class);
        $this->report = app(TrialBalance::class);
        $this->user = User::factory()->create();
    }

    private function account(string $code): Account
    {
        return Account::query()->where('code', $code)->firstOrFail();
    }

    private function postRevenue(string $date, string $description, int $amount, ?int $fundId = null): void
    {
        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse($date),
            description: $description,
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1100')->id, $amount, fundId: $fundId),
                JournalDraftLine::credit($this->account('4-1900')->id, $amount, fundId: $fundId),
            ],
        ));
    }

    public function test_mutasi_and_ending_balance_and_totals(): void
    {
        $this->postRevenue('2026-08-10', 'SPP', 1_000_000);

        // A second revenue account, so rows stay distinct.
        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-20'),
            description: 'Donasi lain',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1100')->id, 250_000),
                JournalDraftLine::credit($this->account('4-1700')->id, 250_000),
            ],
        ));

        $report = $this->report->generate(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));

        $cashRow = collect($report['rows'])->firstWhere('code', '1-1100');
        $revRow = collect($report['rows'])->firstWhere('code', '4-1900');

        $this->assertSame(0, $cashRow['opening_debit']);
        $this->assertSame(1_250_000, $cashRow['mutasi_debit']);
        $this->assertSame(1_250_000, $cashRow['ending_debit']);

        $this->assertSame(1_000_000, $revRow['mutasi_credit']);
        $this->assertSame(1_000_000, $revRow['ending_credit']);

        $this->assertSame(1_250_000, $report['totals']['mutasi_debit']);
        $this->assertSame(1_250_000, $report['totals']['mutasi_credit']);
        $this->assertSame(1_250_000, $report['totals']['ending_debit']);
        $this->assertSame(1_250_000, $report['totals']['ending_credit']);
    }

    public function test_opening_balance_carries_into_the_next_period(): void
    {
        $this->postRevenue('2026-08-10', 'SPP', 600_000);

        $report = $this->report->generate(Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));

        $cashRow = collect($report['rows'])->firstWhere('code', '1-1100');

        $this->assertSame(600_000, $cashRow['opening_debit']);
        $this->assertSame(600_000, $cashRow['ending_debit']);

        $this->assertSame(600_000, $report['totals']['opening_debit']);
        $this->assertSame(600_000, $report['totals']['opening_credit']);
    }

    public function test_void_pair_nets_to_zero_and_is_excluded_from_rows(): void
    {
        $entry = $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-10'),
            description: 'Akan dibatalkan',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1100')->id, 400_000),
                JournalDraftLine::credit($this->account('4-1900')->id, 400_000),
            ],
        ));

        app(JournalPostingService::class)->void($entry, 'Salah input');

        $report = $this->report->generate(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));

        // Gross movement stays visible, but the pair nets to zero.
        $cashRow = collect($report['rows'])->firstWhere('code', '1-1100');

        $this->assertSame(400_000, $cashRow['mutasi_debit']);
        $this->assertSame(400_000, $cashRow['mutasi_credit']);
        $this->assertSame(0, $cashRow['ending_debit']);
        $this->assertSame(0, $report['totals']['ending_debit']);
        $this->assertSame(0, $report['totals']['ending_credit']);
    }

    public function test_fund_filter_slices_lines_and_skips_the_balance_assertion(): void
    {
        $yys = \App\Models\Fund::query()->where('code', 'YYS')->firstOrFail();

        $this->postRevenue('2026-08-10', 'Donasi YYS', 500_000);

        $report = $this->report->generate(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31'),
            $yys->id
        );

        $codes = collect($report['rows'])->pluck('code');

        $this->assertContains('4-1900', $codes);
        $this->assertNotContains('1-1100', $codes);

        $revRow = collect($report['rows'])->firstWhere('code', '4-1900');
        $this->assertSame(500_000, $revRow['mutasi_credit']);
    }

    public function test_corrupted_ledger_throws_on_the_full_statement(): void
    {
        $period = \App\Models\AccountingPeriod::query()->where('name', '2026-08')->firstOrFail();

        $entry = \App\Models\JournalEntry::query()->create([
            'number' => 'JE/2026-08/999200',
            'entry_date' => '2026-08-30',
            'accounting_period_id' => $period->id,
            'description' => 'Jurnal rusak',
            'source' => \App\Enums\JournalSource::Manual,
            'status' => \App\Enums\JournalStatus::Posted,
            'created_by' => $this->user->id,
        ]);

        $entry->lines()->create([
            'account_id' => $this->account('1-1100')->id,
            'fund_id' => null,
            'debit' => 100_000,
            'credit' => 0,
            'memo' => null,
        ]);
        $entry->lines()->create([
            'account_id' => $this->account('4-1900')->id,
            'fund_id' => null,
            'debit' => 0,
            'credit' => 90_000,
            'memo' => null,
        ]);

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Neraca saldo tidak seimbang');

        $this->report->generate(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));
    }
}
