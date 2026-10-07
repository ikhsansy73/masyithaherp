<?php

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Single audit trail for all placement changes (doc 02 §5).
 */
class StudentMovement extends Model
{
    /** @use HasFactory<\Database\Factories\StudentMovementFactory> */
    use HasFactory;

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'type',
        'from_classroom_id',
        'to_classroom_id',
        'movement_date',
        'notes',
        'registered_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => MovementType::class,
            'movement_date' => 'date',
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
     * @return BelongsTo<Classroom, $this>
     */
    public function fromClassroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'from_classroom_id');
    }

    /**
     * @return BelongsTo<Classroom, $this>
     */
    public function toClassroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'to_classroom_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
