<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Jurnal mengajar harian (doc 06 §5). */
class TeacherLearningJournal extends Model
{
    /** @use HasFactory<\Database\Factories\TeacherLearningJournalFactory> */
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'subject_id',
        'teacher_id',
        'date',
        'learning_objective_id',
        'topic',
        'method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
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
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<LearningObjective, $this>
     */
    public function objective(): BelongsTo
    {
        return $this->belongsTo(LearningObjective::class, 'learning_objective_id');
    }

    /**
     * Journals of the classrooms a staff user owns (doc 09 §4).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisibleToStaff(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(['super_admin', 'kepala_sekolah', 'operator_tu', 'bendahara'])) {
            return $query;
        }

        $employeeId = $user->employee?->getKey();

        if ($employeeId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('teacher_id', $employeeId);
    }
}
