<?php

namespace App\Enums;

enum AcademicYearStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Direncanakan',
            self::Active => 'Aktif',
            self::Closed => 'Ditutup',
        };
    }
}
