<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\NormalBalance;
use App\Models\Account;
use App\Models\Fund;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FundSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountSeederTest extends TestCase
{
    use RefreshDatabase;

    private AccountSeeder $accountSeeder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(FundSeeder::class);
        $this->accountSeeder = new AccountSeeder;
    }

    public function test_seeds_the_full_coa_from_doc_03(): void
    {
        ($this->accountSeeder)->run();

        // 49 rows: 7 headers + 42 postable accounts (doc 03 §2).
        $this->assertSame(49, Account::query()->count());
        $this->assertSame(7, Account::query()->where('is_header', true)->count());

        $kas = Account::query()->where('code', '1-1100')->firstOrFail();
        $this->assertSame('Kas', $kas->name);
        $this->assertSame(AccountType::Aset, $kas->type);
        $this->assertSame(NormalBalance::Debit, $kas->normal_balance);
        $this->assertTrue($kas->is_locked);
        $this->assertTrue($kas->is_active);
        $this->assertFalse($kas->is_header);
        $this->assertSame('UMUM', $kas->defaultFund->code);

        $akumulasi = Account::query()->where('code', '1-2900')->firstOrFail();
        $this->assertSame(NormalBalance::Kredit, $akumulasi->normal_balance);

        $spp = Account::query()->where('code', '4-1100')->firstOrFail();
        $this->assertSame('KOM', $spp->defaultFund->code);

        $parent = $kas->parent;
        $this->assertNotNull($parent);
        $this->assertSame('1-1000', $parent->code);
        $this->assertTrue($parent->is_header);
    }

    public function test_every_revenue_and_expense_account_has_operasi_cash_flow(): void
    {
        ($this->accountSeeder)->run();

        // Doc 03 §2: all revenue/expense rows are operasi, except the
        // header rows (no category) and asset-disposal loss 5-1950 (investasi).
        $missing = Account::query()
            ->whereIn('type', [AccountType::Pendapatan, AccountType::Beban])
            ->where('is_header', false)
            ->where('code', '!=', '5-1950')
            ->where(fn ($query) => $query->whereNull('cash_flow_category')
                ->orWhere('cash_flow_category', '!=', 'operasi'))
            ->pluck('code')
            ->all();

        $this->assertSame([], $missing);
    }

    public function test_all_seeded_accounts_are_locked(): void
    {
        ($this->accountSeeder)->run();

        // Every seeded row is locked; only user-added 5-xxxx accounts are not.
        $this->assertSame(0, Account::query()->where('is_locked', false)->count());
    }

    public function test_seeding_is_idempotent(): void
    {
        ($this->accountSeeder)->run();
        ($this->accountSeeder)->run();

        $this->assertSame(49, Account::query()->count());
    }

    public function test_seed_resolves_all_default_funds(): void
    {
        ($this->accountSeeder)->run();

        $this->assertSame(
            0,
            Account::query()->whereNotNull('default_fund_id')->whereNotIn('default_fund_id', Fund::query()->select('id'))->count(),
        );
    }
}
