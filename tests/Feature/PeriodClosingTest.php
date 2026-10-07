<?php

namespace Tests\Feature;

use App\Enums\NormalBalance;
use App\Enums\PeriodStatus;
use App\Exceptions\AccountingException;
use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use App\Services\Accounting\AccountingPeriodService;
use App\Services\Accounting\JournalDraft;
use App\Services\Accounting\JournalDraftLine;
use App\Services\Accounting\JournalPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class PeriodClosingTest extends TestCase
{
    use RefreshDatabase;

    private JournalPostingService $journals;

    private AccountingPeriodService $periods;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([\Database\Seeders\FundSeeder::class, \Database\Seeders\AccountSeeder::class]);

        AcademicYear::factory()->forStartYear(2026)->create();

        $this->journals = app(JournalPostingService::class);
        $this->periods = app(AccountingPeriodService::class);
        $this->user = User::factory()->create();
    }

    private function period(): AccountingPeriod
    {
        return AccountingPeriod::query()->where('name', '2026-08')->firstOrFail();
    }

    private function account(string $code): Account
    {
        return Account::query()->where('code', $code)->firstOrFail();
    }

    /**
     * Ending balance in the account's normal direction, over all entries:
     * a void entry and its mirrored reversal net to zero, so the ledger
     * total must include both.
     */
    private function ending(string $code): int
    {
        $account = $this->account($code);

        $sums = JournalLine::query()
            ->selectRaw('COALESCE(SUM(debit), 0) as debits, COALESCE(SUM(credit), 0) as credits')
            ->where('account_id', $account->id)
            ->firstOrFail();

        return $account->normal_balance === NormalBalance::Kredit
            ? (int) $sums->credits - (int) $sums->debits
            : (int) $sums->debits - (int) $sums->credits;
    }

    private function fundId(string $code): int
    {
        return \App\Models\Fund::query()->where('code', $code)->firstOrFail()->id;
    }

    public function test_tutup_buku_posts_closing_entry_zeroes_nominal_accounts_and_locks_period(): void
    {
        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-10'),
            description: 'SPP Agustus',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1100')->id, 1_000_000),
                JournalDraftLine::credit($this->account('4-1900')->id, 1_000_000),
            ],
        ));

        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-20'),
            description: 'Beli ATK',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('5-1110')->id, 400_000),
                JournalDraftLine::credit($this->account('1-1100')->id, 400_000),
            ],
        ));

        $closed = $this->periods->close($this->period(), $this->user->id);

        $closed->refresh();

        $this->assertSame(PeriodStatus::Closed, $closed->status);
        $this->assertNotNull($closed->closed_at);
        $this->assertSame($this->user->id, $closed->closed_by);

        $closing = $this->periods->closingEntry($closed);

        $this->assertNotNull($closing);
        $this->assertSame('Jurnal penutup Agustus 2026', $closing->description);
        $this->assertSame($closed->getKey(), $closing->reference_id);
        $this->assertSame('2026-08-31', $closing->entry_date->toDateString());

        // Revenue and expense are zero after the closing JE.
        $this->assertSame(0, $this->ending('4-1900'));
        $this->assertSame(0, $this->ending('5-1110'));

        // The month's surplus (1.000.000 - 400.000) sits on 3-1200.
        $this->assertSame(600_000, $this->ending('3-1200'));

        // Closing JE lines: Dr 4-1900, Cr 5-1110, Cr 3-1200.
        $this->assertSame(3, $closing->lines()->count());
        $this->assertSame(1_000_000, (int) $closing->lines()->where('account_id', $this->account('4-1900')->id)->value('debit'));
        $this->assertSame(400_000, (int) $closing->lines()->where('account_id', $this->account('5-1110')->id)->value('credit'));
    }

    public function test_closing_entry_keeps_fund_dimension_consistent(): void
    {
        // Komite revenue and YYS revenue/expense.
        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-10'),
            description: 'DSP',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1100')->id, 500_000),
                JournalDraftLine::credit($this->account('4-1100')->id, 500_000),
            ],
        ));

        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-10'),
            description: 'Donasi & gaji',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1100')->id, 300_000),
                JournalDraftLine::credit($this->account('4-1900')->id, 300_000),
            ],
        ));

        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-15'),
            description: 'Gaji',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('5-1110')->id, 250_000),
                JournalDraftLine::credit($this->account('1-1100')->id, 250_000),
            ],
        ));

        $this->periods->close($this->period(), $this->user->id);
        $closing = $this->periods->closingEntry($this->period());

        // Fund dimension carried onto the closing lines.
        $this->assertSame(
            500_000,
            (int) $closing->lines()
                ->where('account_id', $this->account('3-1200')->id)
                ->where('fund_id', $this->fundId('KOM'))
                ->value('credit')
        );
        $this->assertSame(
            50_000,
            (int) $closing->lines()
                ->where('account_id', $this->account('3-1200')->id)
                ->where('fund_id', $this->fundId('YYS'))
                ->value('credit')
        );
    }

    public function test_close_with_unbalanced_entry_is_rejected(): void
    {
        // Seed a corrupt entry directly (bypassing the posting service).
        $entry = JournalEntry::query()->create([
            'number' => 'JE/2026-08/999100',
            'entry_date' => '2026-08-30',
            'accounting_period_id' => $this->period()->id,
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
            'credit' => 99_999,
            'memo' => null,
        ]);

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Terdapat jurnal tidak seimbang pada periode Agustus 2026.');

        $this->periods->close($this->period(), $this->user->id);
    }

    public function test_close_twice_is_rejected(): void
    {
        $this->periods->close($this->period(), $this->user->id);

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Periode Agustus 2026 sudah ditutup.');

        $this->periods->close($this->period(), $this->user->id);
    }

    public function test_posting_into_closed_period_is_rejected(): void
    {
        $this->periods->close($this->period(), $this->user->id);

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Periode Agustus 2026 sudah ditutup.');

        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-20'),
            description: 'Terlambat',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1100')->id, 100_000),
                JournalDraftLine::credit($this->account('4-1900')->id, 100_000),
            ],
        ));
    }

    public function test_void_in_closed_period_is_rejected(): void
    {
        $entry = $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-10'),
            description: 'Akan dibatalkan setelah tutup buku',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1100')->id, 100_000),
                JournalDraftLine::credit($this->account('4-1900')->id, 100_000),
            ],
        ));

        $this->periods->close($this->period(), $this->user->id);

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Periode Agustus 2026 sudah ditutup.');

        $this->journals->void($entry, 'Salah input');
    }

    public function test_close_of_period_without_entries_posts_no_closing_entry(): void
    {
        $closed = $this->periods->close($this->period(), $this->user->id);

        $this->assertSame(PeriodStatus::Closed, $closed->status);
        $this->assertNull($this->periods->closingEntry($closed));
    }

    public function test_reopen_voids_closing_entry_restores_balances_and_logs_activity(): void
    {
        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-10'),
            description: 'SPP Agustus',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1100')->id, 1_000_000),
                JournalDraftLine::credit($this->account('4-1900')->id, 1_000_000),
            ],
        ));

        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-20'),
            description: 'Beli ATK',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('5-1110')->id, 400_000),
                JournalDraftLine::credit($this->account('1-1100')->id, 400_000),
            ],
        ));

        $this->periods->close($this->period(), $this->user->id);

        $closed = $this->periods->reopen($this->period(), $this->user->id);

        $closed->refresh();

        $this->assertSame(PeriodStatus::Open, $closed->status);
        $this->assertNull($this->periods->closingEntry($closed));
        $this->assertNull($closed->closed_at);

        // Balances restored: the closing JE was voided.
        $this->assertSame(1_000_000, $this->ending('4-1900'));
        $this->assertSame(400_000, $this->ending('5-1110'));
        $this->assertSame(0, $this->ending('3-1200'));

        $this->assertTrue(
            Activity::query()
                ->where('subject_type', (new AccountingPeriod)->getMorphClass())
                ->where('subject_id', $closed->getKey())
                ->where('description', 'Periode Agustus 2026 dibuka kembali.')
                ->exists()
        );
    }

    public function test_reopen_of_open_period_is_rejected(): void
    {
        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Periode Agustus 2026 belum ditutup.');

        $this->periods->reopen($this->period(), $this->user->id);
    }

    public function test_reclose_after_reopen_posts_a_new_closing_entry(): void
    {
        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-10'),
            description: 'SPP Agustus',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1100')->id, 1_000_000),
                JournalDraftLine::credit($this->account('4-1900')->id, 1_000_000),
            ],
        ));

        $this->periods->close($this->period(), $this->user->id);
        $this->periods->reopen($this->period(), $this->user->id);

        // A late correction lands while the month is open again.
        $this->journals->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-25'),
            description: 'Koreksi terlambat',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->account('1-1100')->id, 50_000),
                JournalDraftLine::credit($this->account('4-1900')->id, 50_000),
            ],
        ));

        $this->periods->close($this->period(), $this->user->id);

        $closing = $this->periods->closingEntry($this->period());

        $this->assertNotNull($closing);
        $this->assertSame(0, $this->ending('4-1900'));
        $this->assertSame(1_050_000, $this->ending('3-1200'));
    }
}
