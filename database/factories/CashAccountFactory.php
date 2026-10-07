<?php

namespace Database\Factories;

use App\Enums\CashAccountType;
use App\Models\Account;
use App\Models\CashAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashAccount>
 */
class CashAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Kas Sekolah',
            'type' => CashAccountType::Kas,
            'account_id' => Account::factory(),
            'bank_name' => null,
            'account_number' => null,
            'is_default_kas' => false,
            'is_default_bank' => false,
            'is_active' => true,
        ];
    }

    public function bank(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Bank Sekolah',
            'type' => CashAccountType::Bank,
            'bank_name' => fake()->randomElement(['BCA', 'BRI', 'BSI']),
            'account_number' => fake()->numerify('##########'),
        ]);
    }

    public function defaultKas(): static
    {
        return $this->state(fn (): array => ['is_default_kas' => true]);
    }

    public function defaultBank(): static
    {
        return $this->state(fn (): array => ['is_default_bank' => true]);
    }
}
