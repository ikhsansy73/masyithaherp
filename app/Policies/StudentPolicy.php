<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

/**
 * Doc 09 §4 row scoping: a portal parent (wali_murid) may only view
 * their own children (guardians.user_id). Staff permissions come from
 * the spatie matrix and are unaffected — policies never widen access.
 */
class StudentPolicy
{
    public function view(User $user, Student $student): bool
    {
        if ($user->hasRole('wali_murid')) {
            return $student->guardians()
                ->where('user_id', $user->getKey())
                ->exists();
        }

        return $user->can('students.student.view');
    }

    public function viewAny(User $user): bool
    {
        return $user->can('students.student.viewAny');
    }
}
