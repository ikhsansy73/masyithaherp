<?php

namespace App\Models;

use App\Enums\AcademicYearStatus;
use App\Services\School\AcademicYearService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    /** @use HasFactory<\Database\Factories\AcademicYearFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'starts_at',
        'ends_at',
        'status',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'status' => AcademicYearStatus::class,
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return HasMany<AcademicTerm, $this>
     */
    public function terms(): HasMany
    {
        return $this->hasMany(AcademicTerm::class);
    }

    /**
     * @return HasMany<AccountingPeriod, $this>
     */
    public function periods(): HasMany
    {
        return $this->hasMany(AccountingPeriod::class);
    }

    protected static function booted(): void
    {
        static::created(function (AcademicYear $year) {
            if (! $year->terms()->exists()) {
                app(AcademicYearService::class)->seedTermsAndPeriods($year);
            }
        });
    }
}
