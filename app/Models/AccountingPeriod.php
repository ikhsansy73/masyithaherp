<?php

namespace App\Models;

use App\Enums\PeriodStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingPeriod extends Model
{
    /** @use HasFactory<\Database\Factories\AccountingPeriodFactory> */
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'name',
        'starts_at',
        'ends_at',
        'status',
        'closed_at',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'status' => PeriodStatus::class,
            'closed_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
