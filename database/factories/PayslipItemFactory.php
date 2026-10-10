<?php

namespace Database\Factories;

use App\Enums\SalaryComponentType;
use App\Models\Payslip;
use App\Models\PayslipItem;
use App\Models\SalaryComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayslipItem>
 */
class PayslipItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payslip_id' => Payslip::factory(),
            'salary_component_id' => SalaryComponent::factory(),
            'type' => SalaryComponentType::Pendapatan,
            'amount' => fake()->numberBetween(100_000, 1_000_000),
        ];
    }

    public function potongan(): static
    {
        return $this->state(fn (): array => [
            'type' => SalaryComponentType::Potongan,
        ]);
    }
}
