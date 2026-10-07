<?php

namespace App\Models;

use App\Enums\TermStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicTerm extends Model
{
    /** @use HasFactory<\Database\Factories\AcademicTermFactory> */
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'number',
        'name',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'status' => TermStatus::class,
        ];
    }

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * @return HasMany<CalendarDay, $this>
     */
    public function calendarDays(): HasMany
    {
        return $this->hasMany(CalendarDay::class);
    }
}
