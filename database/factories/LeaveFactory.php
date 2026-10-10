<?php

namespace Database\Factories;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Leave>
 */
class LeaveFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeThisMonth();

        return [
            'employee_id' => Employee::factory(),
            'type' => LeaveType::Izin,
            'start_date' => $start,
            'end_date' => $start,
            'days' => 1,
            'reason' => fake()->sentence(),
            'status' => LeaveStatus::Menunggu,
        ];
    }

    public function disetujui(): static
    {
        return $this->state(fn (): array => [
            'status' => LeaveStatus::Disetujui,
            'approved_at' => now(),
        ]);
    }
}
