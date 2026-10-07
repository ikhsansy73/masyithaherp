<?php

namespace Database\Factories;

use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_no' => 'EMP'.str_pad((string) (Employee::query()->max('id') + 1), 4, '0', STR_PAD_LEFT),
            'name' => fake()->name(),
            'nik' => fake()->unique()->numerify('################'),
            'position' => 'Guru Kelas',
            'employment_status' => EmploymentStatus::Bsm,
            'gender' => fake()->randomElement([Gender::L, Gender::P]),
            'is_teaching' => true,
            'join_date' => fake()->dateTimeBetween('-10 years', '-1 year'),
        ];
    }

    public function nonTeaching(): static
    {
        return $this->state(fn (): array => ['is_teaching' => false]);
    }
}
