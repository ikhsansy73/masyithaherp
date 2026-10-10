<?php

namespace Tests\Feature;

use App\Exceptions\AccountingException;
use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\CashAccount;
use App\Models\InventoryItem;
use App\Models\JournalLine;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FundSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ConsumableCostingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private CashAccount $cash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([FundSeeder::class, AccountSeeder::class]);

        AcademicYear::factory()->forStartYear(2026)->create();

        $this->user = User::factory()->create();

        $this->cash = CashAccount::factory()->create([
            'account_id' => Account::query()->where('code', '1-1100')->value('id'),
        ]);
    }

    private function makeItem(array $overrides = []): InventoryItem
    {
        return InventoryItem::factory()->create($overrides);
    }

    private function perlengkapanBalance(): int
    {
        $accountId = Account::query()->where('code', '1-1400')->value('id');

        return (int) JournalLine::query()
            ->where('account_id', $accountId)
            ->selectRaw('coalesce(sum(debit), 0) - coalesce(sum(credit), 0) as balance')
            ->value('balance');
    }

    public function test_receive_posts_purchase_journal_and_average(): void
    {
        $item = $this->makeItem();

        $movement = app(InventoryService::class)->receive(
            item: $item,
            movementDate: Carbon::parse('2026-08-05'),
            quantity: 10,
            unitCost: 50_000,
            cashAccountId: $this->cash->getKey(),
            userId: $this->user->getKey(),
        );

        $this->assertNotNull($movement->journal_entry_id);

        $lines = $movement->journalEntry->lines->mapWithKeys(
            fn ($line): array => [$line->account->code => $line],
        );
        $this->assertSame(500_000, $lines['1-1400']->debit);
        $this->assertSame(500_000, $lines['1-1100']->credit);

        $item->refresh();
        $this->assertEqualsWithDelta(10, $item->current_stock, 0.001);
        $this->assertSame(50_000, $item->avg_cost);
    }

    public function test_weighted_average_is_recomputed_on_receive(): void
    {
        $item = $this->makeItem();

        $service = app(InventoryService::class);

        $service->receive($item, Carbon::parse('2026-08-05'), 10, 50_000, $this->cash->getKey(), $this->user->getKey());
        $service->receive($item, Carbon::parse('2026-08-20'), 10, 90_000, $this->cash->getKey(), $this->user->getKey());

        $item->refresh();
        $this->assertEqualsWithDelta(20, $item->current_stock, 0.001);
        // (10 × 50.000 + 10 × 90.000) / 20 = 70.000.
        $this->assertSame(70_000, $item->avg_cost);
    }

    public function test_issue_posts_expense_at_average_cost(): void
    {
        $item = $this->makeItem();

        $service = app(InventoryService::class);
        $service->receive($item, Carbon::parse('2026-08-05'), 20, 70_000, $this->cash->getKey(), $this->user->getKey());

        $movement = $service->issue(
            item: $item,
            movementDate: Carbon::parse('2026-08-10'),
            quantity: 5,
            expenseAccountId: (int) Account::query()->where('code', '5-1400')->value('id'),
            userId: $this->user->getKey(),
            purpose: 'Alat tulis kantor bulan Agustus',
        );

        $lines = $movement->journalEntry->lines->mapWithKeys(
            fn ($line): array => [$line->account->code => $line],
        );
        $this->assertSame(350_000, $lines['5-1400']->debit);
        $this->assertSame(350_000, $lines['1-1400']->credit);

        $item->refresh();
        $this->assertEqualsWithDelta(15, $item->current_stock, 0.001);
        $this->assertSame(70_000, $item->avg_cost);
        $this->assertSame(70_000, $movement->unit_cost);
    }

    public function test_issue_can_target_kegiatan_expense(): void
    {
        $item = $this->makeItem();

        $service = app(InventoryService::class);
        $service->receive($item, Carbon::parse('2026-08-05'), 12, 60_000, $this->cash->getKey(), $this->user->getKey());

        $movement = $service->issue(
            item: $item,
            movementDate: Carbon::parse('2026-08-11'),
            quantity: 2,
            expenseAccountId: (int) Account::query()->where('code', '5-1600')->value('id'),
            userId: $this->user->getKey(),
            purpose: 'Kegiatan ekskul',
        );

        $lines = $movement->journalEntry->lines->mapWithKeys(
            fn ($line): array => [$line->account->code => $line],
        );
        $this->assertSame(120_000, $lines['5-1600']->debit);
        $this->assertArrayNotHasKey('5-1400', $lines);
    }

    public function test_issue_rejects_insufficient_stock(): void
    {
        $item = $this->makeItem(['current_stock' => 3]);

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('tidak cukup');

        app(InventoryService::class)->issue(
            item: $item,
            movementDate: Carbon::parse('2026-08-10'),
            quantity: 5,
            expenseAccountId: (int) Account::query()->where('code', '5-1400')->value('id'),
            userId: $this->user->getKey(),
        );
    }

    public function test_receive_requires_active_cash_account(): void
    {
        $item = $this->makeItem();

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Kas/bank tidak aktif atau tidak ditemukan.');

        app(InventoryService::class)->receive(
            item: $item,
            movementDate: Carbon::parse('2026-08-05'),
            quantity: 10,
            unitCost: 50_000,
            cashAccountId: 0,
            userId: $this->user->getKey(),
        );
    }

    public function test_stock_value_matches_1_1400_balance(): void
    {
        $item = $this->makeItem();

        $service = app(InventoryService::class);
        $expense = (int) Account::query()->where('code', '5-1400')->value('id');

        // Receive 10 @ 50.000 → stock 10, balance 500.000.
        $service->receive($item, Carbon::parse('2026-08-05'), 10, 50_000, $this->cash->getKey(), $this->user->getKey());
        $this->assertSame($item->fresh()->stockValue(), $this->perlengkapanBalance());

        // Issue 4 → balance 300.000, stock 6.
        $service->issue($item, Carbon::parse('2026-08-10'), 4, $expense, $this->user->getKey());
        $this->assertSame($item->fresh()->stockValue(), $this->perlengkapanBalance());

        // Receive 10 @ 75.000 → avg (300.000 + 750.000) / 16 = 65.625, balance 1.050.000.
        $service->receive($item, Carbon::parse('2026-08-20'), 10, 75_000, $this->cash->getKey(), $this->user->getKey());
        $this->assertSame(65_625, $item->fresh()->avg_cost);
        $this->assertSame($item->fresh()->stockValue(), $this->perlengkapanBalance());

        // Issue all → balance 0, stock 0.
        $service->issue($item, Carbon::parse('2026-08-25'), 16, $expense, $this->user->getKey());
        $this->assertEqualsWithDelta(0, $item->fresh()->current_stock, 0.001);
        $this->assertSame(0, $this->perlengkapanBalance());
    }
}
