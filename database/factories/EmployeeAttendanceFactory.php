<?php

namespace Database\Factories;

use App\Enums\EmployeeAttendanceStatus;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeAttendance>
 */
class EmployeeAttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'date' => fake()->dateTimeThisMonth()->format('Y-m-d'),
            'check_in' => '07:00:00',
            'check_out' => '14:00:00',
            'status' => EmployeeAttendanceStatus::Hadir,
        ];
    }
}
