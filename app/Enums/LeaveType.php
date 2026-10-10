<?php

namespace App\Enums;

enum LeaveType: string
{
    case Izin = 'izin';
    case Sakit = 'sakit';
    case Cuti = 'cuti';
    case Dinas = 'dinas';

    public function label(): string
    {
        return match ($this) {
            self::Izin => 'Izin',
            self::Sakit => 'Sakit',
            self::Cuti => 'Cuti',
            self::Dinas => 'Dinas',
        };
    }

    /**
     * The attendance status each approved leave day writes (doc 05 §4:
     * one row per employee per day — leave is the source of truth).
     */
    public function attendanceStatus(): EmployeeAttendanceStatus
    {
        return match ($this) {
            self::Izin => EmployeeAttendanceStatus::Izin,
            self::Sakit => EmployeeAttendanceStatus::Sakit,
            self::Cuti => EmployeeAttendanceStatus::Cuti,
            self::Dinas => EmployeeAttendanceStatus::DinasLuar,
        };
    }
}
