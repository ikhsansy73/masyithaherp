<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFee extends Model
{
    /** @use HasFactory<\Database\Factories\StudentFeeFactory> */
    use HasFactory;

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'fee_type_id',
        'amount',
        'months',
        'first_month',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'months' => 'integer',
            'first_month' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * @return BelongsTo<FeeType, $this>
     */
    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }
}
