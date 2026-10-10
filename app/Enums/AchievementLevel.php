<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AchievementLevel: string implements HasLabel
{
    case Sekolah = 'sekolah';

    case Kecamatan = 'kecamatan';

    case Kabupaten = 'kabupaten';

    case Provinsi = 'provinsi';

    case Nasional = 'nasional';

    public function getLabel(): string
    {
        return match ($this) {
            self::Sekolah => 'Sekolah',
            self::Kecamatan => 'Kecamatan',
            self::Kabupaten => 'Kabupaten/Kota',
            self::Provinsi => 'Provinsi',
            self::Nasional => 'Nasional',
        };
    }
}
