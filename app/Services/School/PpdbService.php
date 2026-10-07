<?php

namespace App\Services\School;

use App\Enums\EnrollmentStatus;
use App\Enums\GuardianRelation;
use App\Enums\MovementType;
use App\Enums\PpdbStatus;
use App\Enums\Religion;
use App\Enums\StudentStatus;
use App\Exceptions\SchoolException;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\PpdbRegistration;
use App\Models\Student;
use App\Models\User;
use App\Services\Billing\StudentFeeService;
use App\Services\Shared\DocumentSequenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * PPDB write path (doc 06 §2). Status flow:
 * baru → verifikasi → diterima / cadangan / ditolak; diterima → terdaftar
 * via Daftarkan, which creates student + guardians + enrollment +
 * student_fees (+ movement for transfer-ins) in one transaction.
 */
class PpdbService
{
    public function __construct(
        private readonly DocumentSequenceService $sequences,
        private readonly StudentFeeService $fees,
    ) {}

    /**
     * Create a registration with its sequence number.
     *
     * @param  array<string, mixed>  $data
     */
    public function createRegistration(User $user, array $data): PpdbRegistration
    {
        $year = AcademicYear::query()->findOrFail($data['academic_year_id']);

        return DB::transaction(function () use ($data, $year): PpdbRegistration {
            $number = $this->sequences->next(
                key: 'ppdb',
                period: (string) $year->starts_at->year,
                prefix: 'PPDB/'.$year->starts_at->year.'/',
            );

            return PpdbRegistration::query()->create(array_merge($data, [
                'registration_no' => $number,
                'status' => PpdbStatus::Baru,
                'registered_at' => now(),
            ]));
        });
    }

    /**
     * Verifikasi: baru → verifikasi.
     */
    public function verify(PpdbRegistration $registration, User $user): PpdbRegistration
    {
        if ($registration->status !== PpdbStatus::Baru) {
            throw new SchoolException('Pendaftaran sudah diverifikasi.');
        }

        $registration->forceFill([
            'status' => PpdbStatus::Verifikasi,
            'verified_by' => $user->getKey(),
        ])->save();

        return $registration;
    }

    /**
     * Terima: verifikasi → diterima.
     */
    public function accept(PpdbRegistration $registration, User $user): PpdbRegistration
    {
        if ($registration->status !== PpdbStatus::Verifikasi) {
            throw new SchoolException('Hanya pendaftaran berstatus diverifikasi yang dapat diterima.');
        }

        $registration->update(['status' => PpdbStatus::Diterima]);

        return $registration;
    }

    /**
     * Cadangan: verifikasi → cadangan (waitlist).
     */
    public function putOnHold(PpdbRegistration $registration, User $user): PpdbRegistration
    {
        if ($registration->status !== PpdbStatus::Verifikasi) {
            throw new SchoolException('Hanya pendaftaran berstatus diverifikasi yang dapat dicadangkan.');
        }

        $registration->update(['status' => PpdbStatus::Cadangan]);

        return $registration;
    }

    /**
     * Tolak: verifikasi → ditolak.
     */
    public function reject(PpdbRegistration $registration, User $user): PpdbRegistration
    {
        if ($registration->status !== PpdbStatus::Verifikasi) {
            throw new SchoolException('Hanya pendaftaran berstatus diverifikasi yang dapat ditolak.');
        }

        $registration->update(['status' => PpdbStatus::Ditolak]);

        return $registration;
    }

    /**
     * Daftarkan (doc 06 §2): convert an accepted registration into a real
     * student. One transaction creates the student (NIS from sequence),
     * the guardian rows, the enrollment into the target rombel, the
     * default fees, and (for transfer-ins) a mutasi_masuk movement.
     *
     * @return array{student: Student, created_user: User|null}
     */
    public function daftarkan(
        PpdbRegistration $registration,
        User $actor,
        Classroom $targetClassroom,
        bool $isTransfer = false,
        bool $createPortalAccount = false,
        ?string $guardianEmail = null,
        ?string $waliName = null,
        ?string $waliPhone = null,
    ): array {
        if ($registration->status !== PpdbStatus::Diterima) {
            throw new SchoolException('Hanya pendaftaran berstatus diterima yang dapat didaftarkan.');
        }

        return DB::transaction(function () use (
            $registration,
            $actor,
            $targetClassroom,
            $isTransfer,
            $createPortalAccount,
            $guardianEmail,
            $waliName,
            $waliPhone,
        ): array {
            $year = $registration->academicYear()->lockForUpdate()->firstOrFail();

            if ((int) $targetClassroom->academic_year_id !== (int) $year->getKey()) {
                throw new SchoolException('Rombel target tidak berada di tahun ajaran pendaftaran.');
            }

            $student = Student::query()->create([
                'nis' => $this->sequences->next(key: 'nis', period: 'global', prefix: ''),
                'full_name' => $registration->applicant_name,
                'gender' => $registration->gender,
                'birth_place' => $registration->birth_place,
                'birth_date' => $registration->birth_date,
                'religion' => $registration->religion ?? Religion::Islam,
                'nik' => $registration->nik,
                'address' => $registration->address,
                'status' => StudentStatus::Aktif,
                'entry_date' => today(),
            ]);

            $guardians = [
                [GuardianRelation::Ayah, $registration->father_name, true],
                [GuardianRelation::Ibu, $registration->mother_name, false],
            ];

            foreach ($guardians as [$relation, $name, $primary]) {
                $student->guardians()->create([
                    'relationship' => $relation,
                    'name' => $name,
                    'phone' => $registration->parent_phone,
                    'is_primary_contact' => $primary,
                    'email' => $primary ? $guardianEmail : null,
                    'user_id' => null,
                ]);
            }

            $student->enrollments()->create([
                'academic_year_id' => $year->getKey(),
                'classroom_id' => $targetClassroom->getKey(),
                'grade_level' => $targetClassroom->grade_level,
                'status' => EnrollmentStatus::Aktif,
            ]);

            $this->fees->assignDefaultFees($student, $year, $targetClassroom->grade_level);

            if ($isTransfer) {
                $student->movements()->create([
                    'academic_year_id' => $year->getKey(),
                    'type' => MovementType::MutasiMasuk,
                    'from_classroom_id' => null,
                    'to_classroom_id' => $targetClassroom->getKey(),
                    'movement_date' => today(),
                    'notes' => 'Pindahan pada PPDB',
                    'registered_by' => $actor->getKey(),
                ]);
            }

            if ($waliName !== null && $waliName !== '') {
                $student->guardians()->create([
                    'relationship' => GuardianRelation::Wali,
                    'name' => $waliName,
                    'phone' => $waliPhone ?? $registration->parent_phone,
                    'is_primary_contact' => false,
                    'email' => null,
                    'user_id' => null,
                ]);
            }

            $createdUser = null;

            if ($createPortalAccount && $guardianEmail !== null && $guardianEmail !== '') {
                $createdUser = User::query()->create([
                    'name' => $registration->father_name,
                    'email' => $guardianEmail,
                    'password' => Str::password(12),
                    'is_active' => true,
                ]);

                $createdUser->assignRole(Role::findOrCreate('wali_murid', 'web'));
            }

            if ($createdUser !== null) {
                Guardian::query()
                    ->where('student_id', $student->getKey())
                    ->where('relationship', GuardianRelation::Ayah)
                    ->update(['user_id' => $createdUser->getKey()]);
            }

            $registration->forceFill([
                'status' => PpdbStatus::Terdaftar,
                'converted_student_id' => $student->getKey(),
            ])->save();

            return ['student' => $student, 'created_user' => $createdUser];
        });
    }
}
