<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Enums\InvoiceItemType;
use App\Enums\PaymentMethod;
use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\CashAccount;
use App\Models\Classroom;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\User;
use App\Services\Billing\PaymentService;
use App\Services\Billing\PaymentSource;
use App\Services\School\AcademicYearService;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FundSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TunggakanPageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class, FundSeeder::class, AccountSeeder::class]);

        $this->year = app(AcademicYearService::class)->create('2026/2027', isDefault: true);

        CashAccount::factory()->create([
            'account_id' => Account::query()->where('code', '1-1100')->value('id'),
        ]);

        $this->user = User::factory()->create();
        $this->user->assignRole('bendahara');
    }

    private function enrolledStudent(string $letter = 'A'): Student
    {
        $classroom = Classroom::factory()->grade(1, $letter)->create([
            'academic_year_id' => $this->year->getKey(),
        ]);

        $student = Student::factory()->create();

        $student->enrollments()->create([
            'academic_year_id' => $this->year->getKey(),
            'classroom_id' => $classroom->getKey(),
            'grade_level' => $classroom->grade_level,
            'status' => EnrollmentStatus::Aktif,
        ]);

        return $student;
    }

    private function overdueInvoice(Student $student, int $daysOverdue, int $total): Invoice
    {
        $invoice = Invoice::factory()->issued()->create([
            'student_id' => $student->getKey(),
            'academic_year_id' => $this->year->getKey(),
            'due_date' => Carbon::today()->subDays($daysOverdue),
            'total' => $total,
            'paid_amount' => 0,
            'period_month' => 7,
        ]);

        $invoice->items()->create([
            'item_type' => InvoiceItemType::Posisi,
            'description' => 'SPP bulan 7',
            'amount' => $total,
        ]);

        return $invoice;
    }

    public function test_page_renders_stats_buckets_and_arrears_rows(): void
    {
        $student = $this->enrolledStudent('A');

        $this->overdueInvoice($student, 15, 600_000);

        $this->actingAs($this->user)
            ->get('/admin/tunggakan')
            ->assertOk()
            ->assertSee('Total Tunggakan')
            ->assertSee('600.000')
            ->assertSee('Siswa Menunggak')
            ->assertSee('Tunggakan > 90 Hari')
            ->assertSee('Per Bucket Umur')
            ->assertSee('Pembayaran Belum Dialokasikan')
            ->assertSee('Semua pembayaran sudah dialokasikan.')
            ->assertSee('Daftar Siswa Menunggak')
            ->assertSee($student->full_name)
            ->assertSee('1A')
            ->assertSee('15 hari');
    }

    public function test_page_lists_unallocated_payments(): void
    {
        $student = $this->enrolledStudent('A');
        $this->overdueInvoice($student, 15, 300_000);

        // FIFO fills the 300k invoice; the 200k remainder stays unallocated.
        app(PaymentService::class)->record(new PaymentSource(
            studentId: $student->getKey(),
            paymentDate: today(),
            method: PaymentMethod::Tunai,
            cashAccountId: (int) CashAccount::query()->first()->getKey(),
            amount: 500_000,
            allocations: [],
            userId: $this->user->id,
        ));

        $this->actingAs($this->user)
            ->get('/admin/tunggakan')
            ->assertOk()
            ->assertSee('200.000')
            ->assertSee($student->full_name);
    }

    public function test_page_renders_empty_state_without_arrears(): void
    {
        $this->actingAs($this->user)
            ->get('/admin/tunggakan')
            ->assertOk()
            ->assertSee('Tidak ada tunggakan.');
    }
}
