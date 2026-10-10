<?php

namespace App\Enums;

enum EmployeeAttendanceStatus: string
{
    case Hadir = 'hadir';
    case Terlambat = 'terlambat';
    case Izin = 'izin';
    case Sakit = 'sakit';
    case DinasLuar = 'dinas_luar';
    case Cuti = 'cuti';
    case Alpa = 'alpa';

    public function label(): string
    {
        return match ($this) {
            self::Hadir => 'Hadir',
            self::Terlambat => 'Terlambat',
            self::Izin => 'Izin',
            self::Sakit => 'Sakit',
            self::DinasLuar => 'Dinas Luar',
            self::Cuti => 'Cuti',
            self::Alpa => 'Alpa',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Hadir => 'success',
            self::Terlambat => 'warning',
            self::Izin => 'info',
            self::Sakit => 'info',
            self::DinasLuar => 'info',
            self::Cuti => 'gray',
            self::Alpa => 'danger',
        };
    }
}
