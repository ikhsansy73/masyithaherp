<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Enums\CashFlowCategory;
use App\Enums\NormalBalance;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Default state: a postable expense account in the user-creatable
     * 5-9xxx range (doc 03 §2 — seeded 5-xxxx codes are locked, factory
     * codes use 5-9### so they never collide with the seed).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->numerify('5-9###'),
            'name' => 'Beban '.$this->faker->unique()->words(2, true),
            'type' => AccountType::Beban,
            'normal_balance' => NormalBalance::Debit,
            'is_header' => false,
            'parent_id' => null,
            'cash_flow_category' => CashFlowCategory::Operasi,
            'is_locked' => false,
            'is_active' => true,
        ];
    }

    public function header(): static
    {
        return $this->state(fn (): array => [
            'code' => $this->faker->unique()->numerify('9-####'),
            'name' => 'GRUP '.$this->faker->words(2, true),
            'type' => AccountType::Aset,
            'is_header' => true,
            'is_active' => false,
        ]);
    }

    public function revenue(): static
    {
        return $this->state(fn (): array => [
            'code' => $this->faker->unique()->numerify('4-9###'),
            'name' => 'Pendapatan '.$this->faker->unique()->words(2, true),
            'type' => AccountType::Pendapatan,
            'normal_balance' => NormalBalance::Kredit,
            'cash_flow_category' => CashFlowCategory::Operasi,
        ]);
    }
}
