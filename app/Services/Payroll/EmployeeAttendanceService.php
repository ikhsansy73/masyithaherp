<?php

namespace App\Services\Payroll;

use App\Enums\EmployeeAttendanceStatus;
use App\Exceptions\SchoolException;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use Illuminate\Support\Carbon;

/**
 * Daily staff attendance (doc 05 §4) — one row per employee per day
 * (unique constraint); record() upserts so a re-entry corrects the day.
 */
class EmployeeAttendanceService
{
    public function record(
        Employee $employee,
        Carbon $date,
        EmployeeAttendanceStatus $status,
        ?string $checkIn = null,
        ?string $checkOut = null,
        ?string $notes = null,
    ): EmployeeAttendance {
        if ($date->greaterThan(today())) {
            throw new SchoolException('Absensi pegawai tidak boleh bertanggal masa depan.');
        }

        return EmployeeAttendance::query()->updateOrCreate(
            [
                'employee_id' => $employee->getKey(),
                'date' => $date->toDateString(),
            ],
            [
                'status' => $status,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'notes' => $notes,
            ],
        );
    }
}
