<?php

namespace App\Services\Billing;

use App\Enums\FeeCategory;
use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

/**
 * Assigns default fees (penetapan biaya) to a student for one academic
 * year from the year's fee structures (doc 06 §2 step 4). Per-student
 * deviations are edited afterwards in the StudentFees relation manager.
 */
class StudentFeeService
{
    /**
     * Create one student_fee per fee type with a structure for the year.
     * A grade-matched structure beats the flat one (null grade) for the
     * same fee type. Existing rows win: already-assigned fee types are
     * never duplicated or overwritten.
     *
     * @return int number of rows created
     */
    public function assignDefaultFees(Student $student, AcademicYear $year, int $gradeLevel): int
    {
        $assignedTypeIds = $student->fees()
            ->where('academic_year_id', $year->getKey())
            ->pluck('fee_type_id');

        $structures = FeeStructure::query()
            ->where('academic_year_id', $year->getKey())
            ->whereNotIn('fee_type_id', $assignedTypeIds)
            ->where(fn ($query) => $query
                ->where('grade_level', $gradeLevel)
                ->orWhereNull('grade_level'))
            ->orderBy('fee_type_id')
            ->orderByDesc('grade_level')
            ->with('feeType')
            ->get()
            // Grade-matched beats flat for the same fee type.
            ->unique(fn (FeeStructure $structure): int => $structure->fee_type_id)

            ->values();

        if ($structures->isEmpty()) {
            return 0;
        }

        return (int) DB::transaction(function () use ($student, $structures): int {
            $created = 0;

            foreach ($structures as $structure) {
                $student->fees()->create([
                    'academic_year_id' => $structure->academic_year_id,
                    'fee_type_id' => $structure->fee_type_id,
                    'amount' => $structure->amount,
                    'months' => $structure->feeType->category->defaultMonths(),

                    'first_month' => $structure->feeType->category === FeeCategory::Bulanan
                        ? (int) today()->month
                        : null,
                    'is_active' => true,
                ]);

                $created++;
            }

            return $created;
        });
    }
}
