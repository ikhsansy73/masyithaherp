<?php

namespace Database\Seeders;

use App\Enums\CashAccountType;
use App\Models\Account;
use App\Models\CashAccount;
use Illuminate\Database\Seeder;

/**
 * The physical kas/bank registers mapped to the cash GL accounts
 * (doc 02 §3). AccountSeeder runs first.
 */
class CashAccountSeeder extends Seeder
{
    public function run(): void
    {
        $cashAccounts = [
            ['name' => 'Kas Sekolah', 'type' => CashAccountType::Kas, 'gl_account' => '1-1100', 'is_default_kas' => true, 'is_default_bank' => false],
            ['name' => 'Kas Kecil', 'type' => CashAccountType::Kas, 'gl_account' => '1-1150', 'is_default_kas' => false, 'is_default_bank' => false],
            ['name' => 'Bank Sekolah', 'type' => CashAccountType::Bank, 'gl_account' => '1-1200', 'is_default_kas' => false, 'is_default_bank' => true],
        ];

        foreach ($cashAccounts as $cashAccount) {
            CashAccount::query()->firstOrCreate(
                ['account_id' => Account::query()->where('code', $cashAccount['gl_account'])->value('id')],
                [
                    'name' => $cashAccount['name'],
                    'type' => $cashAccount['type'],
                    'is_default_kas' => $cashAccount['is_default_kas'],
                    'is_default_bank' => $cashAccount['is_default_bank'],
                    'is_active' => true,
                ],
            );
        }
    }
}
