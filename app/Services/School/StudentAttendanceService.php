<?php

namespace App\Services\School;

use App\Enums\StudentAttendanceStatus;
use App\Exceptions\SchoolException;
use App\Models\Classroom;
use App\Models\StudentAttendance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Daily attendance write path (doc 06 §4): one row per student per day
 * (SD model). The grid page upserts the whole rombel at once.
 */
class StudentAttendanceService
{
    /**
     * Save a class grid: $statuses maps student id → status value
     * ('hadir'/'sakit'/'izin'/'alpa'). Re-saving a day updates the
     * existing rows instead of duplicating them.
     *
     * @param  array<int, string>  $statuses
     * @return int rows written
     */
    public function saveForClassroom(
        Classroom $classroom,
        Carbon $date,
        array $statuses,
        User $recordedBy,
    ): int {
        if ($date->isFuture()) {
            throw new SchoolException('Tanggal absensi tidak boleh di masa depan.');
        }

        return (int) DB::transaction(function () use ($classroom, $date, $statuses, $recordedBy): int {
            $written = 0;

            foreach ($statuses as $studentId => $status) {
                if (StudentAttendanceStatus::tryFrom((string) $status) === null) {
                    throw new SchoolException("Status kehadiran '{$status}' tidak valid.");
                }

                StudentAttendance::query()->updateOrCreate(
                    [
                        'student_id' => (int) $studentId,
                        'date' => $date->toDateString(),
                    ],
                    [
                        'classroom_id' => $classroom->getKey(),
                        'status' => $status,
                        'recorded_by' => $recordedBy->getKey(),
                        'notes' => null,
                    ],
                );

                $written++;
            }

            return $written;
        });
    }
}
