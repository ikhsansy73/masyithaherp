<?php

namespace App\Enums;

enum CalendarDayType: string
{
    case Efektif = 'efektif';
    case Libur = 'libur';
    case Ujian = 'ujian';
    case Kegiatan = 'kegiatan';

    public function label(): string
    {
        return match ($this) {
            self::Efektif => 'Hari Efektif',
            self::Libur => 'Libur',
            self::Ujian => 'Ujian',
            self::Kegiatan => 'Kegiatan',
        };
    }
}
