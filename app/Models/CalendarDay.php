<?php

namespace App\Models;

use App\Enums\CalendarDayType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarDay extends Model
{
    /** @use HasFactory<\Database\Factories\CalendarDayFactory> */
    use HasFactory;

    protected $fillable = [
        'date',
        'academic_term_id',
        'type',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'type' => CalendarDayType::class,
        ];
    }

    /**
     * @return BelongsTo<AcademicTerm, $this>
     */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }
}
