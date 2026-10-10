<?php

namespace Tests\Feature;

use App\Enums\MaintenanceType;
use App\Exceptions\AccountingException;
use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\CashAccount;
use App\Models\Fund;
use App\Models\User;
use App\Services\Assets\AssetMaintenanceService;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FundSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AssetMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Asset $asset;

    private CashAccount $cash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([FundSeeder::class, AccountSeeder::class]);

        AcademicYear::factory()->forStartYear(2026)->create();

        $this->user = User::factory()->create();

        $category = AssetCategory::factory()->create([
            'asset_account_id' => Account::query()->where('code', '1-2300')->value('id'),
        ]);

        $this->asset = Asset::factory()->create([
            'asset_category_id' => $category->getKey(),
            'fund_id' => Fund::query()->where('code', 'BOS')->value('id'),
        ]);

        $this->cash = CashAccount::factory()->create([
            'account_id' => Account::query()->where('code', '1-1100')->value('id'),
        ]);
    }

    public function test_cost_posts_expense_to_5_1800(): void
    {
        $maintenance = app(AssetMaintenanceService::class)->record(
            asset: $this->asset,
            maintenanceDate: Carbon::parse('2026-08-05'),
            type: MaintenanceType::Perbaikan,
            description: 'Ganti lampu proyektor',
            cost: 350_000,
            cashAccountId: $this->cash->getKey(),
            vendor: 'CV Sinar',
            userId: $this->user->getKey(),
        );

        $this->assertNotNull($maintenance->journal_entry_id);

        $lines = $maintenance->journalEntry->lines->mapWithKeys(
            fn ($line): array => [$line->account->code => $line],
        );

        // Dr 5-1800 (fund = asset fund) / Cr 1-1100 kas.
        $this->assertSame(350_000, $lines['5-1800']->debit);
        $this->assertSame($this->asset->fund_id, $lines['5-1800']->fund_id);
        $this->assertSame(350_000, $lines['1-1100']->credit);
    }

    public function test_zero_cost_records_note_without_journal(): void
    {
        $maintenance = app(AssetMaintenanceService::class)->record(
            asset: $this->asset,
            maintenanceDate: Carbon::parse('2026-08-06'),
            type: MaintenanceType::Perawatan,
            description: 'Kontrol rutin',
            cost: 0,
            cashAccountId: null,
            vendor: null,
            userId: $this->user->getKey(),
        );

        $this->assertNull($maintenance->journal_entry_id);
        $this->assertSame(0, $maintenance->cost);
        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('journal_entries')->count());
    }

    public function test_nonzero_cost_requires_active_cash_account(): void
    {
        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Kas/bank tidak aktif atau tidak ditemukan.');

        app(AssetMaintenanceService::class)->record(
            asset: $this->asset,
            maintenanceDate: Carbon::parse('2026-08-05'),
            type: MaintenanceType::Perawatan,
            description: 'Cek rutin unit',
            cost: 100_000,
            cashAccountId: null,
            vendor: null,
            userId: $this->user->getKey(),
        );
    }
}
