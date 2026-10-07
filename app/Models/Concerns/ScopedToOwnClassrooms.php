<?php

namespace App\Models\Concerns;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Doc 09 §4 row scoping for academic staff: guru sees the classrooms
 * they teach (class_subject_teachers), wali_kelas sees the rombel they
 * homeroom. Admin roles bypass the filter. Applied in each resource's
 * getEloquentQuery() / page queries — deliberately NOT a global scope.
 */
trait ScopedToOwnClassrooms
{
    /**
     * Restrict a classroom-scoped query to the rombel the user owns.
     *
     * @param  Builder<Classroom>  $query
     * @return Builder<Classroom>
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
                ->where('homeroom_teacher_id', $employeeId)
                ->orWhereHas('classSubjectTeachers', fn (Builder $cst): Builder => $cst
                    ->where('teacher_id', $employeeId));
        });
    }
}
