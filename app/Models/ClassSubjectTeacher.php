<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSubjectTeacher extends Model
{
    /** @use HasFactory<\Database\Factories\ClassSubjectTeacherFactory> */
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'subject_id',
        'teacher_id',
        'jp_per_week',
    ];

    protected function casts(): array
    {
        return [
            'jp_per_week' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Classroom, $this>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'teacher_id');
    }
}
