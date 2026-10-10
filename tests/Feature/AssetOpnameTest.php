<?php

namespace Tests\Feature;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetOpnameItem;
use App\Models\Fund;
use App\Models\User;
use App\Services\Assets\AssetOpnameService;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FundSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AssetOpnameTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private AssetCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([FundSeeder::class, AccountSeeder::class]);

        AcademicYear::factory()->forStartYear(2026)->create();

        $this->user = User::factory()->create();

        $this->category = AssetCategory::factory()->create([
            'asset_account_id' => Account::query()->where('code', '1-2300')->value('id'),
        ]);
    }

    private function makeAsset(array $overrides = []): Asset
    {
        return Asset::factory()->create(array_merge([
            'asset_category_id' => $this->category->getKey(),
            'fund_id' => Fund::query()->where('code', 'BOS')->value('id'),
        ], $overrides));
    }

    public function test_create_snapshots_active_assets(): void
    {
        $a = $this->makeAsset();
        $b = $this->makeAsset();
        $sold = $this->makeAsset(['status' => AssetStatus::Dijual]);

        $result = app(AssetOpnameService::class)->create(
            name: 'Opname Semester 1',
            opnameDate: Carbon::parse('2026-10-10'),
            userId: $this->user->getKey(),
        );

        $this->assertSame(2, $result['itemCount']);
        $this->assertSame(
            AssetOpnameItem::query()->count(),
            AssetOpnameItem::query()->where('found', true)->count(),
        );
        $this->assertDatabaseMissing('asset_opname_items', ['asset_id' => $sold->getKey()]);
        $this->assertDatabaseHas('asset_opname_items', ['asset_id' => $a->getKey(), 'found' => true]);
        $this->assertDatabaseHas('asset_opname_items', ['asset_id' => $b->getKey(), 'found' => true]);
    }

    public function test_create_can_scope_to_location(): void
    {
        $this->makeAsset();
        $this->makeAsset();

        $result = app(AssetOpnameService::class)->create(
            name: 'Opname Lab',
            opnameDate: Carbon::parse('2026-10-10'),
            userId: $this->user->getKey(),
            locationId: \App\Models\Location::factory()->create()->getKey(),
        );

        $this->assertSame(0, $result['itemCount']);
    }

    public function test_finalize_updates_condition_for_found_items(): void
    {
        $asset = $this->makeAsset(['condition' => AssetCondition::Baik]);

        $result = app(AssetOpnameService::class)->create(
            name: 'Opname Kondisi',
            opnameDate: Carbon::parse('2026-10-10'),
            userId: $this->user->getKey(),
        );

        AssetOpnameItem::query()
            ->where('asset_opname_id', $result['opname']->getKey())
            ->where('asset_id', $asset->getKey())
            ->update(['condition' => AssetCondition::RusakRingan]);

        $result = app(AssetOpnameService::class)->finalize($result['opname'], $this->user->getKey());

        $this->assertSame(1, $result['conditionsUpdated']);
        $this->assertSame(0, $result['missingDisposed']);
        $this->assertSame(AssetCondition::RusakRingan, $asset->refresh()->condition);
        $this->assertSame(0, \DB::table('journal_entries')->count());
    }

    public function test_finalize_disposes_missing_assets(): void
    {
        $asset = $this->makeAsset(['acquisition_cost' => 12_000_000]);

        $result = app(AssetOpnameService::class)->create(
            name: 'Opname Hilang',
            opnameDate: Carbon::parse('2026-10-10'),
            userId: $this->user->getKey(),
        );

        AssetOpnameItem::query()
            ->where('asset_opname_id', $result['opname']->getKey())
            ->where('asset_id', $asset->getKey())
            ->update(['found' => false]);

        $result = app(AssetOpnameService::class)->finalize($result['opname'], $this->user->getKey());

        $this->assertSame(0, $result['conditionsUpdated']);
        $this->assertSame(1, $result['missingDisposed']);
        $this->assertSame(AssetStatus::Hilang, $asset->refresh()->status);
        $this->assertNotNull($asset->disposal_journal_entry_id);

        $lines = $asset->disposalJournalEntry->lines->mapWithKeys(
            fn ($line): array => [$line->account->code => $line],
        );
        $this->assertSame(12_000_000, $lines['5-1950']->debit);
        $this->assertSame(12_000_000, $lines['1-2300']->credit);
        $this->assertArrayNotHasKey('1-2900', $lines);
    }

    public function test_finalize_is_safe_to_rerun(): void
    {
        $asset = $this->makeAsset(['acquisition_cost' => 12_000_000]);

        $result = app(AssetOpnameService::class)->create(
            name: 'Opname Ulang',
            opnameDate: Carbon::parse('2026-10-10'),
            userId: $this->user->getKey(),
        );

        AssetOpnameItem::query()
            ->where('asset_opname_id', $result['opname']->getKey())
            ->where('asset_id', $asset->getKey())
            ->update(['found' => false]);

        app(AssetOpnameService::class)->finalize($result['opname'], $this->user->getKey());
        $second = app(AssetOpnameService::class)->finalize($result['opname'], $this->user->getKey());

        $this->assertSame(0, $second['missingDisposed']);
        $this->assertSame(1, \DB::table('journal_entries')->count());
    }
}
