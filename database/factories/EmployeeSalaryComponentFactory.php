<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\SalaryComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeSalaryComponent>
 */
class EmployeeSalaryComponentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'salary_component_id' => SalaryComponent::factory(),
            'amount' => fake()->numberBetween(500_000, 3_000_000),
            'is_active' => true,
        ];
    }
}
