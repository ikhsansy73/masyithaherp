<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Models\Concerns\ScopedToOwnClassrooms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Classroom extends Model
{
    /** @use HasFactory<\Database\Factories\ClassroomFactory> */
    use HasFactory, ScopedToOwnClassrooms;

    protected $fillable = [
        'academic_year_id',
        'name',
        'grade_level',
        'fase',
        'homeroom_teacher_id',
        'location_id',
        'capacity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'grade_level' => 'integer',
            'capacity' => 'integer',
            'is_active' => 'boolean',
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
     * @return BelongsTo<Employee, $this>
     */
    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'homeroom_teacher_id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return HasMany<StudentEnrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    /**
     * Enrollments currently active in this classroom (same year).
     *
     * @return HasMany<StudentEnrollment, $this>
     */
    public function activeEnrollments(): HasMany
    {
        return $this->enrollments()
            ->where('status', EnrollmentStatus::Aktif)
            ->where('academic_year_id', $this->academic_year_id);
    }

    /**
     * @return HasMany<ClassSubjectTeacher, $this>
     */
    public function classSubjectTeachers(): HasMany
    {
        return $this->hasMany(ClassSubjectTeacher::class);
    }

    /**
     * @return HasMany<Schedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * @return HasMany<StudentAttendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    /**
     * Kurikulum Merdeka fase from grade: 1–2 → A, 3–4 → B, 5–6 → C.
     */
    public static function faseForGrade(int $grade): string
    {
        return match (true) {
            $grade >= 1 && $grade <= 2 => 'A',
            $grade >= 3 && $grade <= 4 => 'B',
            $grade >= 5 && $grade <= 6 => 'C',
            default => throw new InvalidArgumentException('Tingkat kelas harus 1-6.'),
        };
    }

    /**
     * Classrooms of one academic year (e.g. for the promotion wizard).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForYear(Builder $query, int $academicYearId): Builder
    {
        return $query->where('academic_year_id', $academicYearId)->orderBy('name');
    }
}
