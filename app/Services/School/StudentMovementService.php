<?php

namespace App\Services\School;

use App\Enums\EnrollmentStatus;
use App\Enums\MovementType;
use App\Enums\StudentStatus;
use App\Exceptions\SchoolException;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Placement write path: end-of-year promotion wizard + mid-year exits
 * (doc 06 §3). All placement changes land in student_movements as the
 * single audit trail.
 */
class StudentMovementService
{
    /**
     * Decisions for the promotion wizard.
     */
    public const DECISIONS = ['naik', 'tinggal_kelas', 'lulus', 'mutasi_keluar', 'keluar'];

    /**
     * Run the promotion for one rombel into the target year. $decisions
     * maps student id → decision ('naik' | 'tinggal_kelas' | 'lulus' |
     * 'mutasi_keluar' | 'keluar'). Returns the number of students moved.
     * The wizard covers a whole rombel; for a mid-year exit use
     * moveStudent() with the same guards.
     *
     * @param  array<int, string>  $decisions
     */
    public function promoteClassroom(
        Classroom $from,
        AcademicYear $targetYear,
        array $decisions,
        User $actor,
        Carbon $movementDate,
        ?string $notes = null,
    ): int {
        $sourceYear = $from->academicYear;

        if ($targetYear->starts_at->lte($sourceYear->ends_at)) {
            throw new SchoolException('Tahun ajaran target harus setelah tahun ajaran rombel asal.');
        }

        return (int) DB::transaction(function () use ($from, $targetYear, $decisions, $actor, $movementDate, $notes, $sourceYear): int {
            AcademicYear::query()->whereKey($targetYear->getKey())->lockForUpdate()->firstOrFail();

            $students = Student::query()
                ->whereIn('id', array_keys($decisions))
                ->get()
                ->keyBy('id');

            foreach ($decisions as $studentId => $decision) {
                $student = $students->get($studentId);

                if ($student === null) {
                    throw new SchoolException("Siswa dengan ID {$studentId} tidak ditemukan.");
                }

                if (! in_array($decision, self::DECISIONS, true)) {
                    throw new SchoolException("Keputusan '{$decision}' tidak valid untuk {$student->full_name}.");
                }

                $enrollment = $student->enrollments()
                    ->where('academic_year_id', $sourceYear->getKey())
                    ->where('classroom_id', $from->getKey())
                    ->first();

                if ($enrollment === null || $enrollment->status !== EnrollmentStatus::Aktif) {
                    throw new SchoolException("Siswa {$student->full_name} tidak terdaftar aktif di rombel asal.");
                }

                $grade = $enrollment->grade_level;

                $movementType = match ($decision) {
                    'naik' => MovementType::Kenaikan,
                    'tinggal_kelas' => MovementType::TinggalKelas,
                    'lulus' => MovementType::Lulus,
                    'mutasi_keluar' => MovementType::MutasiKeluar,
                    default => MovementType::Keluar,
                };

                if ($decision === 'naik' && $grade >= 6) {
                    throw new SchoolException("Siswa kelas 6 tidak dapat naik kelas; gunakan keputusan lulus untuk {$student->full_name}.");
                }

                if ($decision === 'lulus' && $grade < 6) {
                    throw new SchoolException("Hanya siswa kelas 6 yang dapat diluluskan: {$student->full_name}.");
                }

                if (in_array($decision, ['naik', 'tinggal_kelas'], true)
                    && $student->enrollments()->where('academic_year_id', $targetYear->getKey())->exists()) {
                    throw new SchoolException("Siswa {$student->full_name} sudah terdaftar di tahun ajaran target.");
                }

                $toClassroom = null;

                if ($decision === 'naik') {
                    $toClassroom = $this->findOrCreateClassroom($targetYear, $grade + 1, $from);
                }

                if ($decision === 'tinggal_kelas') {
                    $toClassroom = $this->findOrCreateClassroom($targetYear, $grade, $from);
                }

                $student->movements()->create([
                    'academic_year_id' => $sourceYear->getKey(),
                    'type' => $movementType,
                    'from_classroom_id' => $from->getKey(),
                    'to_classroom_id' => $toClassroom?->getKey(),
                    'movement_date' => $movementDate->copy()->toDateString(),
                    'notes' => $notes,
                    'registered_by' => $actor->getKey(),
                ]);

                if ($toClassroom !== null) {
                    $student->enrollments()->create([
                        'academic_year_id' => $targetYear->getKey(),
                        'classroom_id' => $toClassroom->getKey(),
                        'grade_level' => $toClassroom->grade_level,
                        'status' => EnrollmentStatus::Aktif,
                    ]);
                }

                if ($decision === 'lulus') {
                    $enrollment->update(['status' => EnrollmentStatus::Lulus]);
                    $student->update(['status' => StudentStatus::Lulus]);
                }

                if ($decision === 'mutasi_keluar') {
                    $enrollment->update(['status' => EnrollmentStatus::Pindah]);
                    $student->update([
                        'status' => StudentStatus::MutasiKeluar,
                        'exit_date' => $movementDate->copy()->toDateString(),
                        'exit_reason' => $notes,
                    ]);
                }

                if ($decision === 'keluar') {
                    $enrollment->update(['status' => EnrollmentStatus::Keluar]);
                    $student->update([
                        'status' => StudentStatus::Keluar,
                        'exit_date' => $movementDate->toDateString(),
                        'exit_reason' => $notes,
                    ]);
                }
            }

            return count($decisions);
        });
    }

    /**
     * Mid-year single-student exit (mutasi keluar / keluar).
     */
    public function moveStudent(
        Student $student,
        string $decision,
        User $actor,
        Carbon $movementDate,
        ?string $notes = null,
    ): void {
        $enrollment = $student->enrollments()
            ->where('status', EnrollmentStatus::Aktif)
            ->orderByDesc('academic_year_id')
            ->first();

        if ($enrollment === null) {
            throw new SchoolException('Siswa tidak memiliki status terdaftar aktif.');
        }

        if (! in_array($decision, ['mutasi_keluar', 'keluar'], true)) {
            throw new SchoolException('Keputusan harus mutasi_keluar atau keluar.');
        }

        DB::transaction(function () use ($student, $decision, $actor, $movementDate, $notes, $enrollment): void {
            $classroom = $enrollment->classroom;

            $student->movements()->create([
                'academic_year_id' => $enrollment->academic_year_id,
                'type' => $decision === 'mutasi_keluar' ? MovementType::MutasiKeluar : MovementType::Keluar,
                'from_classroom_id' => $classroom?->getKey(),
                'to_classroom_id' => null,
                'movement_date' => $movementDate->toDateString(),
                'notes' => $notes,
                'registered_by' => $actor->getKey(),
            ]);

            $enrollment->update(['status' => $decision === 'mutasi_keluar'
                ? EnrollmentStatus::Pindah
                : EnrollmentStatus::Keluar,
            ]);

            $student->update([
                'status' => $decision === 'mutasi_keluar' ? StudentStatus::MutasiKeluar : StudentStatus::Keluar,
                'exit_date' => $movementDate->toDateString(),
                'exit_reason' => $notes,
            ]);
        });
    }

    /**
     * Target rombel in the new year with the same rombel letter; created
     * on the fly when the new year has not been set up yet (doc 06 §3
     * "auto-target next grade's classroom of the new year").
     */
    private function findOrCreateClassroom(AcademicYear $year, int $grade, Classroom $source): Classroom
    {
        $letter = preg_replace('/^\d+/', '', $source->name);

        if ($letter === $source->name || $letter === '') {
            $letter = 'A';
        }

        return Classroom::query()
            ->where('academic_year_id', $year->getKey())
            ->where('name', $grade.$letter)
            ->first()
            ?? Classroom::query()->create([
                'academic_year_id' => $year->getKey(),
                'name' => $grade.$letter,
                'grade_level' => $grade,
                'fase' => Classroom::faseForGrade($grade),
                'is_active' => true,
            ]);
    }
}
