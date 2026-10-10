<?php

namespace Tests\Feature;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\FundingSource;
use App\Exceptions\AccountingException;
use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\CashAccount;
use App\Models\Fund;
use App\Models\Location;
use App\Models\User;
use App\Services\Assets\AcquisitionData;
use App\Services\Assets\AssetService;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FundSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssetAcquisitionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([FundSeeder::class, AccountSeeder::class]);

        AcademicYear::factory()->forStartYear(2026)->create();

        $this->user = User::factory()->create();
    }

    private function acquireData(array $overrides = []): AcquisitionData
    {
        $category = AssetCategory::factory()->create([
            'asset_account_id' => Account::query()->where('code', '1-2300')->value('id'),
            'useful_life_months' => 48,
            'is_depreciable' => true,
        ]);

        $location = Location::factory()->create();
        $cash = CashAccount::factory()->create([
            'account_id' => Account::query()->where('code', '1-1100')->value('id'),
        ]);

        $defaults = [
            'categoryId' => (int) $category->getKey(),
            'name' => 'Proyektor Ruang Kelas 5A',
            'acquisitionDate' => Carbon::parse('2026-08-05'),
            'cost' => 12_000_000,
            'fundId' => (int) Fund::query()->firstOrFail()->getKey(),
            'fundingSource' => FundingSource::Bos,
            'condition' => AssetCondition::Baik,
            'locationId' => (int) $location->getKey(),
            'cashAccountId' => (int) $cash->getKey(),
            'userId' => $this->user->getKey(),
        ];

        return new AcquisitionData(...array_merge($defaults, $overrides));
    }

    public function test_acquire_posts_balanced_je12_with_fund_on_asset_line(): void
    {
        $data = $this->acquireData();

        $asset = app(AssetService::class)->acquire($data);

        $asset->refresh();

        $this->assertMatchesRegularExpression('/^INV-Aset\/2026\/\d{6}$/', $asset->code);
        $this->assertSame(AssetStatus::Aktif, $asset->status);
        $this->assertNotNull($asset->journal_entry_id);

        $lines = $asset->journalEntry->lines->mapWithKeys(
            fn ($line): array => [$line->account->code => $line],
        );

        // JE #12: Dr 1-2300 (fund = asset fund) / Cr 1-1100 kas.
        $this->assertSame(12_000_000, $lines['1-2300']->debit);
        $this->assertSame($data->fundId, $lines['1-2300']->fund_id);
        $this->assertSame(12_000_000, $lines['1-1100']->credit);
        // fundId null on the credit line falls back to the kas account's
        // default fund (UMUM).
        $this->assertSame(
            Account::query()->where('code', '1-1100')->value('default_fund_id'),
            $lines['1-1100']->fund_id,
        );
        $this->assertSame($lines['1-2300']->debit, $lines['1-1100']->credit);
    }

    public function test_credit_purchase_posts_credit_to_utang_vendor(): void
    {
        $data = $this->acquireData(['paymentMode' => 'utang', 'cashAccountId' => null]);

        $asset = app(AssetService::class)->acquire($data);

        $lines = $asset->journalEntry->lines->mapWithKeys(
            fn ($line): array => [$line->account->code => $line],
        );

        // JE #12 beli-kredit: Cr 2-1400 Utang Vendor.
        $this->assertSame(12_000_000, $lines['2-1400']->credit);
        $this->assertSame(12_000_000, $lines['1-2300']->debit);
    }

    public function test_duplicate_posts_second_je_and_fresh_code(): void
    {
        $service = app(AssetService::class);
        $asset = $service->acquire($this->acquireData());

        $duplicate = $service->duplicate($asset, $this->user->getKey());

        $this->assertNotSame($asset->getKey(), $duplicate->getKey());
        $this->assertNotSame($asset->code, $duplicate->code);
        $this->assertSame(2, Asset::query()->count());
        $this->assertSame(2, DB::table('journal_entries')->count());
        $this->assertSame(2, Asset::query()->whereNotNull('journal_entry_id')->count());
    }

    public function test_duplicate_rejects_non_active_asset(): void
    {
        $service = app(AssetService::class);
        $asset = $service->acquire($this->acquireData());
        $asset->forceFill(['status' => AssetStatus::Dihapuskan])->save();

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Hanya aset berstatus aktif yang dapat diduplikat.');

        $service->duplicate($asset, $this->user->getKey());
    }

    public function test_duplicate_rejects_asset_without_acquisition_je(): void
    {
        $service = app(AssetService::class);
        $asset = Asset::factory()->create([
            'fund_id' => Fund::query()->firstOrFail()->getKey(),
        ]);

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Aset sumber belum memiliki jurnal akuisisi.');

        $service->duplicate($asset, $this->user->getKey());
    }

    public function test_zero_cost_rejected_with_indonesian_message(): void
    {
        $data = $this->acquireData(['cost' => 0]);

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Biaya perolehan aset harus lebih besar dari nol.');

        app(AssetService::class)->acquire($data);
    }
}
