<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\DisposalMethod;
use App\Enums\JournalSource;
use App\Enums\JournalStatus;
use App\Exceptions\AccountingException;
use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDepreciation;
use App\Models\AssetOpname;
use App\Models\AssetOpnameItem;
use App\Models\CashAccount;
use App\Models\Fund;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Assets\AssetDisposalService;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FundSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AssetDisposalTest extends TestCase
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

    private function makeAsset(int $cost = 12_000_000): Asset
    {
        $category = AssetCategory::factory()->create([
            'asset_account_id' => Account::query()->where('code', '1-2300')->value('id'),
            'useful_life_months' => 48,
            'is_depreciable' => true,
        ]);

        return Asset::factory()->create([
            'asset_category_id' => $category->getKey(),
            'acquisition_date' => Carbon::parse('2026-06-15'),
            'acquisition_cost' => $cost,
            'salvage_value' => 0,
            'fund_id' => Fund::query()->where('code', 'BOS')->value('id'),
        ]);
    }

    /**
     * Depreciation history must be backed by its own JE — the schema
     * requires one per row.
     */
    private function addHistory(Asset $asset, int $amount): void
    {
        $entry = JournalEntry::query()->create([
            'number' => 'JE/2026-07/'.str_pad((string) JournalEntry::query()->count(), 6, '0', STR_PAD_LEFT),
            'entry_date' => '2026-07-31',
            'accounting_period_id' => AccountingPeriod::query()->where('name', '2026-07')->value('id'),
            'description' => 'Simulasi penyusutan',
            'source' => JournalSource::Otomatis,
            'status' => JournalStatus::Posted,
            'created_by' => $this->user->getKey(),
        ]);

        AssetDepreciation::query()->create([
            'asset_id' => $asset->getKey(),
            'accounting_period_id' => AccountingPeriod::query()->where('name', '2026-07')->value('id'),
            'amount' => $amount,
            'accumulated_amount' => $amount,
            'journal_entry_id' => $entry->getKey(),
        ]);
    }

    public function test_disposal_with_proceeds_posts_gain(): void
    {
        $asset = $this->makeAsset();

        $result = app(AssetDisposalService::class)->dispose(
            $asset,
            Carbon::parse('2026-08-10'),
            DisposalMethod::Dijual,
            13_000_000,
            $this->cash->getKey(),
            $this->user->getKey(),
        );

        $result->refresh();

        $this->assertSame(AssetStatus::Dijual, $result->status);
        $this->assertSame(13_000_000, $result->disposal_proceeds);
        $this->assertNotNull($result->disposal_journal_entry_id);

        $lines = $result->disposalJournalEntry->lines->mapWithKeys(
            fn ($line): array => [$line->account->code => $line],
        );

        // Rule #14, NBV 12jt sold at 13jt: Dr kas 13jt / Cr 1-2300 12jt +
        // Cr 4-1900 1jt. No depreciation yet, so no 1-2900 line.
        $this->assertSame(13_000_000, $lines['1-1100']->debit);
        $this->assertSame(12_000_000, $lines['1-2300']->credit);
        $this->assertSame(1_000_000, $lines['4-1900']->credit);
        $this->assertArrayNotHasKey('1-2900', $lines);
        $this->assertSame($result->fund_id, $lines['4-1900']->fund_id);
    }

    public function test_disposal_with_proceeds_posts_loss(): void
    {
        $asset = $this->makeAsset();
        $this->addHistory($asset, 4_000_000);

        $result = app(AssetDisposalService::class)->dispose(
            $asset,
            Carbon::parse('2026-08-10'),
            DisposalMethod::Dijual,
            5_000_000,
            $this->cash->getKey(),
            $this->user->getKey(),
        );

        $lines = $result->disposalJournalEntry->lines->mapWithKeys(
            fn ($line): array => [$line->account->code => $line],
        );

        // Rule #14, NBV 8jt sold at 5jt: Dr kas 5jt + Dr 1-2900 4jt +
        // Dr 5-1950 3jt / Cr 1-2300 12jt.
        $this->assertSame(5_000_000, $lines['1-1100']->debit);
        $this->assertSame(4_000_000, $lines['1-2900']->debit);
        $this->assertSame(3_000_000, $lines['5-1950']->debit);
        $this->assertSame(12_000_000, $lines['1-2300']->credit);
        $this->assertArrayNotHasKey('4-1900', $lines);
    }

    public function test_disposal_without_proceeds_posts_nbv_to_expense(): void
    {
        $asset = $this->makeAsset();
        $this->addHistory($asset, 4_000_000);

        $result = app(AssetDisposalService::class)->dispose(
            $asset,
            Carbon::parse('2026-08-10'),
            DisposalMethod::Dihapuskan,
            null,
            null,
            $this->user->getKey(),
        );

        $result->refresh();

        $this->assertSame(AssetStatus::Dihapuskan, $result->status);
        $this->assertSame(0, $result->disposal_proceeds);

        $lines = $result->disposalJournalEntry->lines->mapWithKeys(
            fn ($line): array => [$line->account->code => $line],
        );

        // Rule #15: Dr 1-2900 4jt + Dr 5-1950 8jt (NBV) / Cr 1-2300 12jt.
        $this->assertSame(4_000_000, $lines['1-2900']->debit);
        $this->assertSame(8_000_000, $lines['5-1950']->debit);
        $this->assertSame(12_000_000, $lines['1-2300']->credit);
    }

    public function test_disposal_fully_depreciated_without_proceeds_has_no_expense_line(): void
    {
        $asset = $this->makeAsset();
        $this->addHistory($asset, 12_000_000);

        $result = app(AssetDisposalService::class)->dispose(
            $asset,
            Carbon::parse('2026-08-10'),
            DisposalMethod::Dihapuskan,
            null,
            null,
            $this->user->getKey(),
        );

        $lines = $result->disposalJournalEntry->lines->mapWithKeys(
            fn ($line): array => [$line->account->code => $line],
        );

        // NBV 0: only Dr 1-2900 12jt / Cr 1-2300 12jt — no 5-1950 stub.
        $this->assertSame(12_000_000, $lines['1-2900']->debit);
        $this->assertSame(12_000_000, $lines['1-2300']->credit);
        $this->assertArrayNotHasKey('5-1950', $lines);
    }

    public function test_hilang_requires_opname_finding(): void
    {
        $asset = $this->makeAsset();

        try {
            app(AssetDisposalService::class)->dispose(
                $asset,
                Carbon::parse('2026-08-10'),
                DisposalMethod::Hilang,
                null,
                null,
                $this->user->getKey(),
            );

            $this->fail('Expected AccountingException.');
        } catch (AccountingException $exception) {
            $this->assertSame('Penghapusan dengan status hilang wajib memiliki temuan opname (aset tidak ditemukan).', $exception->getMessage());
        }

        $opname = AssetOpname::query()->create([
            'name' => 'Opname Semester 1',
            'opname_date' => '2026-08-01',
            'conducted_by' => $this->user->getKey(),
        ]);

        AssetOpnameItem::query()->create([
            'asset_opname_id' => $opname->getKey(),
            'asset_id' => $asset->getKey(),
            'found' => false,
        ]);

        $result = app(AssetDisposalService::class)->dispose(
            $asset,
            Carbon::parse('2026-08-10'),
            DisposalMethod::Hilang,
            null,
            null,
            $this->user->getKey(),
        );

        $this->assertSame(AssetStatus::Hilang, $result->status);
    }

    public function test_disposal_rejects_non_active_asset(): void
    {
        $asset = $this->makeAsset();
        $asset->forceFill(['status' => AssetStatus::Dihapuskan])->save();

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Hanya aset berstatus aktif yang dapat dihapuskan.');

        app(AssetDisposalService::class)->dispose(
            $asset,
            Carbon::parse('2026-08-10'),
            DisposalMethod::Dihapuskan,
            null,
            null,
            $this->user->getKey(),
        );
    }

    public function test_dijual_requires_proceeds_and_active_cash(): void
    {
        $asset = $this->makeAsset();

        try {
            app(AssetDisposalService::class)->dispose(
                $asset,
                Carbon::parse('2026-08-10'),
                DisposalMethod::Dijual,
                0,
                $this->cash->getKey(),
                $this->user->getKey(),
            );

            $this->fail('Expected AccountingException.');
        } catch (AccountingException $exception) {
            $this->assertSame('Hasil penjualan aset wajib lebih besar dari nol.', $exception->getMessage());
        }

        $inactive = CashAccount::factory()->create([
            'account_id' => Account::query()->where('code', '1-1200')->value('id'),
            'is_active' => false,
        ]);

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Kas/bank penerima hasil penjualan tidak aktif atau tidak ditemukan.');

        app(AssetDisposalService::class)->dispose(
            $asset,
            Carbon::parse('2026-08-10'),
            DisposalMethod::Dijual,
            13_000_000,
            $inactive->getKey(),
            $this->user->getKey(),
        );
    }
}
