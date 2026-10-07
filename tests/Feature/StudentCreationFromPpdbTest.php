<?php

namespace Tests\Feature;

use App\Enums\PpdbStatus;
use App\Exceptions\SchoolException;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\FeeStructure;
use App\Models\FeeType;
use App\Models\PpdbRegistration;
use App\Models\User;
use App\Services\School\PpdbService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCreationFromPpdbTest extends TestCase
{
    use RefreshDatabase;

    private PpdbService $service;

    private User $operator;

    private AcademicYear $year;

    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PpdbService::class);
        $this->operator = User::factory()->create();
        $this->operator->assignRole(\Spatie\Permission\Models\Role::findOrCreate('operator_tu', 'web'));
        $this->year = AcademicYear::factory()->create();

        $fund = \App\Models\Fund::factory()->create();

        FeeType::factory()->bulanan()->create(['code' => 'SPP', 'name' => 'SPP']);
        FeeType::factory()->create(['code' => 'DSP', 'name' => 'Dana Pendidikan']);
        FeeStructure::factory()->create([
            'academic_year_id' => $this->year->getKey(),
            'fee_type_id' => FeeType::query()->where('code', 'SPP')->sole()->getKey(),
            'grade_level' => null,
            'fund_id' => $fund->getKey(),
            'amount' => 600_000,
        ]);
        FeeStructure::factory()->create([
            'academic_year_id' => $this->year->getKey(),
            'fee_type_id' => FeeType::query()->where('code', 'DSP')->sole()->getKey(),
            'grade_level' => null,
            'fund_id' => $fund->getKey(),
            'amount' => 1_500_000,
        ]);
        $this->classroom = Classroom::factory()->grade(1)->create([
            'academic_year_id' => $this->year->getKey(),
        ]);
    }

    private function registration(): PpdbRegistration
    {
        return PpdbRegistration::factory()->forYear($this->year)->create([
            'applicant_name' => 'Aisyah Putri',
            'father_name' => 'Budi Santoso',
            'mother_name' => 'Siti Aminah',
            'parent_phone' => '081234567890',
        ]);
    }

    public function test_daftarkan_creates_student_guardians_enrollment_and_fees(): void
    {
        $registration = $this->service->accept(
            $this->service->verify($this->registration(), $this->operator),
            $this->operator,
        );

        $result = $this->service->daftarkan(
            registration: $registration,
            actor: $this->operator,
            targetClassroom: $this->classroom,
            isTransfer: true,
            createPortalAccount: true,
            guardianEmail: 'budi@example.com',
        );

        $student = $result['student'];

        $this->assertNotNull($student->nis);
        $this->assertSame('Aisyah Putri', $student->full_name);
        $this->assertSame(PpdbStatus::Terdaftar, $registration->fresh()->status);
        $this->assertSame($student->getKey(), $registration->fresh()->converted_student_id);

        $this->assertCount(2, $student->guardians);
        $ayah = $student->guardians->firstWhere('relationship', 'ayah');
        $this->assertNotNull($ayah);
        $this->assertTrue($ayah->is_primary_contact);
        $this->assertSame('budi@example.com', $ayah->email);
        $this->assertNotNull($result['created_user']);
        $this->assertSame($ayah->fresh()->user_id, $result['created_user']->getKey());
        $this->assertTrue($result['created_user']->hasRole('wali_murid'));

        $enrollment = $student->enrollments()->sole();
        $this->assertSame($this->classroom->getKey(), $enrollment->classroom_id);
        $this->assertSame($this->year->getKey(), $enrollment->academic_year_id);

        $this->assertGreaterThanOrEqual(1, $student->fees()->count());
        $this->assertDatabaseHas('student_fees', [
            'student_id' => $student->getKey(),
            'academic_year_id' => $this->year->getKey(),
        ]);

        $this->assertDatabaseHas('student_movements', [
            'student_id' => $student->getKey(),
            'type' => 'mutasi_masuk',
            'to_classroom_id' => $this->classroom->getKey(),
        ]);
    }

    public function test_daftarkan_rejects_registration_not_accepted(): void
    {
        $this->expectException(SchoolException::class);
        $this->expectExceptionMessage('Hanya pendaftaran berstatus diterima yang dapat didaftarkan.');

        $this->service->daftarkan(
            registration: $this->registration(),
            actor: $this->operator,
            targetClassroom: $this->classroom,
        );
    }

    public function test_daftarkan_adds_wali_guardian_when_named(): void
    {
        $registration = $this->service->accept(
            $this->service->verify($this->registration(), $this->operator),
            $this->operator,
        );

        $result = $this->service->daftarkan(
            registration: $registration,
            actor: $this->operator,
            targetClassroom: $this->classroom,
            waliName: 'Pak Warsa',
            waliPhone: '089876543210',
        );

        $this->assertNull($result['created_user']);
        $this->assertCount(3, $result['student']->guardians);
        $this->assertDatabaseHas('guardians', [
            'student_id' => $result['student']->getKey(),
            'relationship' => 'wali',
            'name' => 'Pak Warsa',
        ]);
    }

    public function test_daftarkan_skips_portal_account_without_email(): void
    {
        $registration = $this->service->accept(
            $this->service->verify($this->registration(), $this->operator),
            $this->operator,
        );

        $result = $this->service->daftarkan(
            registration: $registration,
            actor: $this->operator,
            targetClassroom: $this->classroom,
            createPortalAccount: true,
        );

        $this->assertNull($result['created_user']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_status_flow_baru_to_terdaftar_is_gated(): void
    {
        $registration = $this->registration();

        $this->service->verify($registration, $this->operator);
        $this->assertSame(PpdbStatus::Verifikasi, $registration->status);
        $this->assertNotNull($registration->verified_by);

        $this->service->accept($registration, $this->operator);
        $this->assertSame(PpdbStatus::Diterima, $registration->status);

        $this->expectException(SchoolException::class);
        $this->service->accept($registration, $this->operator);
    }
}
