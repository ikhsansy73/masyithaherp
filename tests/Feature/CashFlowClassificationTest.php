<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\CashFlowCategory;
use App\Enums\NormalBalance;
use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\User;
use App\Services\Accounting\JournalDraft;
use App\Services\Accounting\JournalDraftLine;
use App\Services\Accounting\JournalPostingService;
use App\Services\Accounting\Reports\CashFlow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CashFlowClassificationTest extends TestCase
{
    use RefreshDatabase;

    private JournalPostingService $journals;

    private CashFlow $report;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([\Database\Seeders\FundSeeder::class, \Database\Seeders\AccountSeeder::class]);

        AcademicYear::factory()->forStartYear(2026)->create();

        $this->journals = app(JournalPostingService::class);
        $this->report = app(CashFlow::class);
        $this->user = User::factory()->create();
    }

    private function account(string $code): Account
    {
        return Account::query()->where('code', $code)->firstOrFail();
    }

    /**
     * Post a two-line entry: cash account 1-1100 on one side, the given
     * counterpart account on the other.
     */
    private function cashEntry(string $date, string $description, Account $counterpart, int $amount, string $cashSide): void
    {
        $cash = $this->account('1-1100');

        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse($date),
            description: $description,
            userId: $this->user->id,
            lines: $cashSide === 'debit'
                ? [
                    JournalDraftLine::debit($cash->id, $amount),
                    JournalDraftLine::credit($counterpart->id, $amount),
                ]
                : [
                    JournalDraftLine::debit($counterpart->id, $amount),
                    JournalDraftLine::credit($cash->id, $amount),
                ],
        ));
    }

    private function generate(string $from, string $to): array
    {
        return $this->report->generate(Carbon::parse($from), Carbon::parse($to));
    }

    public function test_operating_inflow_is_classified(): void
    {
        $this->cashEntry('2026-08-10', 'SPP tunai', $this->account('4-1900'), 500_000, 'debit');

        $report = $this->generate('2026-08-01', '2026-08-31');

        $this->assertSame(500_000, $report['operasi']['in']);
        $this->assertSame(0, $report['operasi']['out']);
        $this->assertSame(500_000, $report['operasi']['net']);
        $this->assertSame(500_000, $report['closing']);
    }

    public function test_investing_outflow_is_classified(): void
    {
        $this->cashEntry('2026-08-10', 'Beli mesin', $this->account('1-2300'), 300_000, 'credit');

        $report = $this->generate('2026-08-01', '2026-08-31');

        $this->assertSame(300_000, $report['investasi']['out']);
        $this->assertSame(-300_000, $report['investasi']['net']);
        $this->assertSame(-300_000, $report['closing']);
    }

    public function test_pendanaan_bucket_classifies_funding_lines(): void
    {
        $hibah = Account::factory()->create([
            'code' => '2-1600',
            'type' => AccountType::Kewajiban,
            'normal_balance' => NormalBalance::Kredit,
            'cash_flow_category' => CashFlowCategory::Pendanaan,
        ]);

        $this->cashEntry('2026-08-10', 'Hibah diterima', $hibah, 750_000, 'debit');

        $report = $this->generate('2026-08-01', '2026-08-31');

        $this->assertSame(750_000, $report['pendanaan']['in']);
        $this->assertSame(750_000, $report['pendanaan']['net']);
    }

    public function test_transfer_between_cash_accounts_lands_in_non_kas_and_ties(): void
    {
        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-10'),
            description: 'Setor kas ke bank',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1200')->id, 200_000),
                JournalDraftLine::credit($this->account('1-1100')->id, 200_000),
            ],
        ));

        $report = $this->generate('2026-08-01', '2026-08-31');

        $this->assertSame(0, $report['operasi']['net']);
        $this->assertSame(0, $report['non_kas']['net']);

        // Total cash unchanged: closing equals opening.
        $this->assertSame(0, $report['opening']);
        $this->assertSame(0, $report['closing']);

        // Per cash account, the transfer is visible on both sides.
        $this->assertSame(-200_000, $report['accounts']['1-1100']['closing']);
        $this->assertSame(200_000, $report['accounts']['1-1200']['closing']);
    }

    public function test_compound_entry_splits_across_categories(): void
    {
        $hibah = Account::factory()->create([
            'code' => '2-1600',
            'type' => AccountType::Kewajiban,
            'normal_balance' => NormalBalance::Kredit,
            'cash_flow_category' => CashFlowCategory::Pendanaan,
        ]);

        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-10'),
            description: 'Setoran gabungan',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1100')->id, 600_000),
                JournalDraftLine::credit($this->account('4-1900')->id, 500_000),
                JournalDraftLine::credit($hibah->id, 100_000),
            ],
        ));

        $report = $this->generate('2026-08-01', '2026-08-31');

        $this->assertSame(500_000, $report['operasi']['in']);
        $this->assertSame(100_000, $report['pendanaan']['in']);
        $this->assertSame(600_000, $report['closing']);
    }

    public function test_opening_and_closing_cash_per_account(): void
    {
        $this->cashEntry('2026-08-05', 'SPP', $this->account('4-1900'), 400_000, 'debit');

        $report = $this->generate('2026-09-01', '2026-09-30');

        $this->assertSame(400_000, $report['opening']);
        $this->assertSame(400_000, $report['closing']);
        $this->assertSame(400_000, $report['accounts']['1-1100']['opening']);
        $this->assertSame(0, $report['accounts']['1-1200']['opening']);
    }
}
