<?php

namespace Database\Factories;

use App\Enums\SalaryCalculation;
use App\Enums\SalaryComponentType;
use App\Models\Account;
use App\Models\SalaryComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalaryComponent>
 */
class SalaryComponentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('KOMP_???'),
            'name' => fake()->words(2, true),
            'type' => SalaryComponentType::Pendapatan,
            'calculation' => SalaryCalculation::Fixed,
            'default_amount' => fake()->numberBetween(100_000, 2_000_000),
            'gl_account_id' => Account::query()->where('code', '5-1120')->value('id'),
        ];
    }

    public function potongan(): static
    {
        return $this->state(fn (): array => [
            'type' => SalaryComponentType::Potongan,
            'gl_account_id' => null,
            'liability_account_id' => Account::query()->where('code', '2-1100')->value('id'),
        ]);
    }

    public function percentBase(float $rate): static
    {
        return $this->state(fn (): array => [
            'calculation' => SalaryCalculation::PercentBase,
            'default_amount' => 0,
            'percent_rate' => $rate,
        ]);
    }

    public function manualEntry(): static
    {
        return $this->state(fn (): array => [
            'calculation' => SalaryCalculation::ManualEntry,
        ]);
    }

    public function employer(): static
    {
        return $this->state(fn (): array => [
            'is_employer' => true,
            'liability_account_id' => Account::query()->where('code', '2-1200')->value('id'),
        ]);
    }
}
