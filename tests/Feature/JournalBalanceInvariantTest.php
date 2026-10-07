<?php

namespace Tests\Feature;

use App\Enums\JournalSource;
use App\Enums\JournalStatus;
use App\Enums\PeriodStatus;
use App\Exceptions\AccountingException;
use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Accounting\JournalDraft;
use App\Services\Accounting\JournalDraftLine;
use App\Services\Accounting\JournalPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class JournalBalanceInvariantTest extends TestCase
{
    use RefreshDatabase;

    private JournalPostingService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([\Database\Seeders\FundSeeder::class, \Database\Seeders\AccountSeeder::class]);

        AcademicYear::factory()->forStartYear(2026)->create();

        $this->service = app(JournalPostingService::class);
        $this->user = User::factory()->create();
    }

    private function cash(): Account
    {
        return Account::query()->where('code', '1-1100')->firstOrFail();
    }

    private function revenue(): Account
    {
        return Account::query()->where('code', '4-1900')->firstOrFail();
    }

    public function test_balanced_entry_posts_with_number_lines_and_default_fund(): void
    {
        $entry = $this->service->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-15'),
            description: 'Terima donasi tunai',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->cash()->id, 500_000),
                JournalDraftLine::credit($this->revenue()->id, 500_000),
            ],
        ));

        $entry->refresh();

        $this->assertSame(JournalStatus::Posted, $entry->status);
        $this->assertMatchesRegularExpression('/^JE\/2026-08\/\d{6}$/', $entry->number);
        $this->assertSame(2, $entry->lines()->count());
        $this->assertSame(PeriodStatus::Open, $entry->accountingPeriod->status);

        // Fund dimension pre-filled from the accounts' default funds.
        $cashLine = $entry->lines()->where('account_id', $this->cash()->id)->first();
        $revenueLine = $entry->lines()->where('account_id', $this->revenue()->id)->first();
        $this->assertSame('UMUM', $cashLine->fund->code);
        $this->assertSame('YYS', $revenueLine->fund->code);
    }

    public function test_explicit_fund_on_the_line_wins_over_account_default(): void
    {
        $bosFund = \App\Models\Fund::query()->where('code', 'BOS')->firstOrFail();

        $entry = $this->service->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-15'),
            description: 'Donasi atas nama BOS',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->cash()->id, 250_000, fundId: $bosFund->id),
                JournalDraftLine::credit($this->revenue()->id, 250_000, fundId: $bosFund->id),
            ],
        ));

        $entry->lines->each(
            fn (\App\Models\JournalLine $line) => $this->assertSame($bosFund->id, $line->fund_id),
        );
    }

    public function test_unbalanced_entry_is_rejected(): void
    {
        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Total debit harus sama dengan total kredit.');

        $this->service->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-15'),
            description: 'Tidak seimbang',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->cash()->id, 500_000),
                JournalDraftLine::credit($this->revenue()->id, 499_999),
            ],
        ));
    }

    public function test_zero_total_entry_is_rejected(): void
    {
        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Total debit harus sama dengan total kredit.');

        $this->service->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-15'),
            description: 'Nol',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->cash()->id, 0),
                JournalDraftLine::credit($this->revenue()->id, 0),
            ],
        ));
    }

    public function test_header_account_is_rejected(): void
    {
        $header = Account::query()->where('code', '1-0000')->firstOrFail();

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Akun 1-0000 adalah akun grup dan tidak dapat diposting.');

        $this->service->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-15'),
            description: 'Pakai akun grup',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($header->id, 100_000),
                JournalDraftLine::credit($this->revenue()->id, 100_000),
            ],
        ));
    }

    public function test_inactive_account_is_rejected(): void
    {
        $account = Account::factory()->create(['is_active' => false]);

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage("Akun {$account->code} tidak aktif dan tidak dapat diposting.");

        $this->service->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-15'),
            description: 'Akun nonaktif',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->cash()->id, 100_000),
                JournalDraftLine::credit($account->id, 100_000),
            ],
        ));
    }

    public function test_future_entry_date_is_rejected(): void
    {
        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Tanggal jurnal tidak boleh di masa depan.');

        $this->service->post(new JournalDraft(
            entryDate: now()->addMonth(),
            description: 'Masa depan',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->cash()->id, 100_000),
                JournalDraftLine::credit($this->revenue()->id, 100_000),
            ],
        ));
    }

    public function test_date_outside_any_period_is_rejected(): void
    {
        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Tidak ada periode akuntansi untuk Juni 2026.');

        $this->service->post(new JournalDraft(
            entryDate: Carbon::parse('2026-06-15'),
            description: 'Di luar periode',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->cash()->id, 100_000),
                JournalDraftLine::credit($this->revenue()->id, 100_000),
            ],
        ));
    }

    public function test_single_line_entry_is_rejected(): void
    {
        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Jurnal minimal memiliki dua baris.');

        $this->service->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-15'),
            description: 'Satu baris',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->cash()->id, 100_000),
            ],
        ));
    }

    public function test_line_with_both_debit_and_credit_is_rejected(): void
    {
        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Baris jurnal tidak boleh memiliki debit dan kredit sekaligus.');

        $this->service->post(new JournalDraft(
            entryDate: Carbon::parse('2026-08-15'),
            description: 'Dua sisi satu baris',
            userId: $this->user->id,
            lines: [
                new JournalDraftLine($this->cash()->id, debit: 100_000, credit: 100_000),
                JournalDraftLine::credit($this->revenue()->id, 100_000),
            ],
        ));
    }

    public function test_check_constraint_rejects_both_sides_at_database_level(): void
    {
        // Layer 2 of the balance invariant (doc 03 §3.2): the CHECK
        // constraint on journal_lines rejects a line carrying both sides.
        $period = AccountingPeriod::query()->where('name', '2026-08')->firstOrFail();
        $entry = JournalEntry::query()->create([
            'number' => 'JE/2026-08/999001',
            'entry_date' => '2026-08-31',
            'accounting_period_id' => $period->id,
            'description' => 'Uji CHECK constraint',
            'source' => JournalSource::Manual,
            'status' => JournalStatus::Posted,
            'created_by' => $this->user->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $entry->lines()->create([
            'account_id' => $this->cash()->id,
            'fund_id' => null,
            'debit' => 50_000,
            'credit' => 50_000,
            'memo' => null,
        ]);
    }

    public function test_numbers_increment_within_a_month(): void
    {
        $first = $this->service->post($this->draft());
        $second = $this->service->post($this->draft());
        $third = $this->service->post($this->draft());

        $this->assertSame('JE/2026-08/000001', $first->number);
        $this->assertSame('JE/2026-08/000002', $second->number);
        $this->assertSame('JE/2026-08/000003', $third->number);
    }

    public function test_numbers_reset_per_month(): void
    {
        $august = $this->service->post($this->draft());
        $september = $this->service->post(new JournalDraft(
            entryDate: Carbon::parse('2026-09-01'),
            description: 'September',
            userId: $this->user->id,
            lines: $this->draft()->lines,
        ));

        $this->assertSame('JE/2026-08/000001', $august->number);
        $this->assertSame('JE/2026-09/000001', $september->number);
    }

    public function test_void_creates_mirrored_reversal_and_marks_original(): void
    {
        $entry = $this->service->post($this->draft());
        $originalLines = $entry->lines()->orderBy('id')->get();

        $reversal = $this->service->void($entry, 'Salah input', 'Pembatalan uji');

        $reversal->refresh();
        $entry->refresh();

        $this->assertSame(JournalStatus::Void, $entry->status);
        $this->assertNotNull($entry->voided_at);
        $this->assertSame('Salah input', $entry->voided_reason);
        $this->assertSame($reversal->id, $entry->voided_by_entry_id);
        $this->assertSame(JournalStatus::Posted, $reversal->status);
        $this->assertSame(JournalSource::Manual, $reversal->source);

        // Mirror: every line's debit/credit swapped, same accounts and funds.
        $reversalLines = $reversal->lines()->orderBy('account_id')->get();
        $this->assertSame($originalLines->count(), $reversalLines->count());

        foreach ($originalLines as $original) {
            $mirror = $reversalLines->firstWhere('account_id', $original->account_id);

            $this->assertNotNull($mirror);
            $this->assertSame((int) $original->debit, (int) $mirror->credit);
            $this->assertSame((int) $original->credit, (int) $mirror->debit);
            $this->assertSame($original->fund_id, $mirror->fund_id);
        }
    }

    public function test_voiding_an_already_void_entry_is_rejected(): void
    {
        $entry = $this->service->post($this->draft());
        $this->service->void($entry, 'Pertama');

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Jurnal sudah dibatalkan.');

        $this->service->void($entry, 'Kedua');
    }

    public function test_posted_entry_cannot_be_deleted(): void
    {
        $entry = $this->service->post($this->draft());

        $this->expectException(\LogicException::class);

        $entry->delete();
    }

    public function test_posted_line_cannot_be_updated_or_deleted(): void
    {
        $entry = $this->service->post($this->draft());
        $line = $entry->lines()->first();

        $this->expectException(\LogicException::class);
        $line->update(['debit' => 1]);
    }

    private function draft(): JournalDraft
    {
        return new JournalDraft(
            entryDate: Carbon::parse('2026-08-15'),
            description: 'Jurnal uji '.Str::random(4),
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($this->cash()->id, 100_000),
                JournalDraftLine::credit($this->revenue()->id, 100_000),
            ],
        );
    }
}
