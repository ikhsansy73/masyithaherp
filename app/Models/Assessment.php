<?php

namespace App\Models;

use App\Enums\AssessmentDimension;
use App\Enums\AssessmentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Penilaian per rombel × mapel × term (doc 06 §6). */
class Assessment extends Model
{
    /** @use HasFactory<\Database\Factories\AssessmentFactory> */
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'subject_id',
        'teacher_id',
        'academic_term_id',
        'name',
        'type',
        'dimension',
        'assessment_date',
        'max_score',
        'learning_objective_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => AssessmentType::class,
            'dimension' => AssessmentDimension::class,
            'assessment_date' => 'date',
            'max_score' => 'decimal:2',
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
     * @return BelongsTo<AcademicTerm, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'academic_term_id');
    }

    /**
     * @return BelongsTo<LearningObjective, $this>
     */
    public function objective(): BelongsTo
    {
        return $this->belongsTo(LearningObjective::class, 'learning_objective_id');
    }

    /**
     * @return HasMany<AssessmentScore, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class);
    }

    /**
     * Assessments of the classrooms a staff user owns (doc 09 §4).
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

        return $query->where(function (Builder $inner) use ($employeeId): void {
            $inner
                ->where('teacher_id', $employeeId)
                ->orWhereHas('classroom', fn (Builder $classroom): Builder => $classroom
                    ->where('homeroom_teacher_id', $employeeId));
        });
    }
}
