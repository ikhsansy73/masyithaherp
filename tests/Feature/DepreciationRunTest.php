<?php

namespace Tests\Feature;

use App\Enums\JournalSource;
use App\Enums\JournalStatus;
use App\Enums\PeriodStatus;
use App\Exceptions\AccountingException;
use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDepreciation;
use App\Models\Fund;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Assets\DepreciationService;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FundSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DepreciationRunTest extends TestCase
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
            'useful_life_months' => 48,
            'is_depreciable' => true,
        ]);
    }

    private function makeAsset(array $overrides = []): Asset
    {
        return Asset::factory()->create(array_merge([
            'asset_category_id' => $this->category->getKey(),
            'acquisition_date' => Carbon::parse('2026-06-15'),
            'acquisition_cost' => 12_000_000,
            'salvage_value' => 0,
            'useful_life_months' => 48,
            'fund_id' => Fund::query()->where('code', 'BOS')->value('id'),
        ], $overrides));
    }

    private function period(string $name): AccountingPeriod
    {
        return AccountingPeriod::query()->where('name', $name)->firstOrFail();
    }

    public function test_run_posts_je13_with_straight_line_amounts(): void
    {
        $fundBos = Fund::query()->where('code', 'BOS')->firstOrFail();
        $fundYys = Fund::query()->where('code', 'YYS')->firstOrFail();

        $this->makeAsset(['fund_id' => $fundBos->getKey()]);
        $this->makeAsset(['acquisition_cost' => 9_600_000, 'fund_id' => $fundYys->getKey()]);

        $result = app(DepreciationService::class)->run($this->period('2026-08'), $this->user->getKey());

        $this->assertSame(2, $result->count);
        $this->assertNotNull($result->entry);
        $this->assertMatchesRegularExpression('/^JE\/2026-08\/\d{6}$/', $result->entry->number);

        // Entry date = period ends_at.
        $this->assertTrue($result->entry->entry_date->isSameDay($this->period('2026-08')->ends_at));

        $lines = $result->entry->lines->mapWithKeys(
            fn ($line): array => [$line->account->code.'-'.$line->fund_id => $line],
        );

        // JE #13: Dr 5-1300 per fund / Cr 1-2900 per fund.
        $this->assertSame(250_000, $lines['5-1300-'.$fundBos->getKey()]->debit);
        $this->assertSame(200_000, $lines['5-1300-'.$fundYys->getKey()]->debit);
        $this->assertSame(250_000, $lines['1-2900-'.$fundBos->getKey()]->credit);
        $this->assertSame(200_000, $lines['1-2900-'.$fundYys->getKey()]->credit);
        $this->assertSame(450_000, $result->entry->lines->sum('debit'));
    }

    public function test_run_skips_non_depreciable_and_future_acquisitions(): void
    {
        $tanah = AssetCategory::factory()->create([
            'asset_account_id' => Account::query()->where('code', '1-2100')->value('id'),
            'is_depreciable' => false,
        ]);

        $this->makeAsset(); // layak
        $this->makeAsset(['asset_category_id' => $tanah->getKey()]); // tanah
        $this->makeAsset(['acquisition_date' => Carbon::parse('2026-09-01')]); // future

        $result = app(DepreciationService::class)->run($this->period('2026-08'), $this->user->getKey());

        $this->assertSame(1, $result->count);
    }

    public function test_amount_capped_at_remaining_nbv(): void
    {
        $asset = $this->makeAsset();

        // Simulate history: a July depreciation row backed by its own JE
        // (the schema requires one per row). Accumulated 11,800,000 leaves
        // remaining depreciable = 200,000 < monthly 250,000, so the August
        // run must cap at 200,000 and land NBV exactly on salvage (0).
        $historyEntry = JournalEntry::query()->create([
            'number' => 'JE/2026-07/000001',
            'entry_date' => '2026-07-31',
            'accounting_period_id' => $this->period('2026-07')->getKey(),
            'description' => 'Simulasi penyusutan Juli',
            'source' => JournalSource::Otomatis,
            'status' => JournalStatus::Posted,
            'created_by' => $this->user->getKey(),
        ]);

        AssetDepreciation::query()->create([
            'asset_id' => $asset->getKey(),
            'accounting_period_id' => $this->period('2026-07')->getKey(),
            'amount' => 11_800_000,
            'accumulated_amount' => 11_800_000,
            'journal_entry_id' => $historyEntry->getKey(),
        ]);

        $result = app(DepreciationService::class)->run($this->period('2026-08'), $this->user->getKey());

        $this->assertSame(1, $result->count);
        $this->assertSame(200_000, $asset->depreciations()->where('accounting_period_id', $this->period('2026-08')->getKey())->value('amount'));
        $this->assertSame(12_000_000, $asset->accumulatedDepreciation());
        $this->assertSame(0, $asset->netBookValue());
    }

    public function test_rerun_is_idempotent(): void
    {
        $service = app(DepreciationService::class);
        $this->makeAsset();

        $first = $service->run($this->period('2026-08'), $this->user->getKey());
        $second = $service->run($this->period('2026-08'), $this->user->getKey());

        $this->assertSame(1, $first->count);
        $this->assertSame(0, $second->count);
        $this->assertNull($second->entry);
        $this->assertSame(1, AssetDepreciation::query()->count());
        $this->assertSame(1, DB::table('journal_entries')->count());
    }

    public function test_closed_period_rejected(): void
    {
        $this->makeAsset();

        $period = $this->period('2026-08');
        $period->forceFill(['status' => PeriodStatus::Closed])->save();

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Periode 2026-08 sudah ditutup.');

        app(DepreciationService::class)->run($period, $this->user->getKey());
    }

    public function test_command_runs_previous_month_by_default(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-15'));

        try {
            $this->artisan('assets:depreciate')
                ->expectsOutput('Tidak ada aset yang perlu disusutkan untuk 2026-09.')
                ->assertSuccessful();
        } finally {
            Carbon::setTestNow();
        }
    }
}
