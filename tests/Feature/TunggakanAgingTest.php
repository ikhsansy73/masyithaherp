<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\CashAccount;
use App\Models\Classroom;
use App\Models\Discount;
use App\Models\Fund;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Services\Billing\ArrearsService;
use App\Services\Billing\PaymentService;
use App\Services\Billing\PaymentSource;
use App\Services\School\AcademicYearService;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FundSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TunggakanAgingTest extends TestCase
{
    use RefreshDatabase;

    private ArrearsService $service;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([FundSeeder::class, AccountSeeder::class]);
        $this->year = app(AcademicYearService::class)->create('2026/2027', isDefault: true);

        CashAccount::factory()->create([
            'account_id' => Account::query()->where('code', '1-1100')->value('id'),
        ]);

        $this->actingAs(User::factory()->create());
        $this->service = app(ArrearsService::class);
    }

    /**
     * Student enrolled in a named classroom of the default year.
     */
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

    /**
     * Overdue invoice for the student; optionally partially paid.
     */
    private function overdueInvoice(Student $student, int $daysOverdue, int $total, int $paid = 0, int $periodMonth = 7): Invoice
    {
        $invoice = Invoice::factory()->issued()->create([
            'student_id' => $student->getKey(),
            'academic_year_id' => $this->year->getKey(),
            'fund_id' => $this->fundId(),
            'due_date' => Carbon::today()->subDays($daysOverdue),
            'total' => $total,
            'paid_amount' => $paid,
            'period_month' => $periodMonth,
        ]);

        if ($paid > 0) {
            $invoice->forceFill(['status' => InvoiceStatus::PartiallyPaid])->save();
        }

        $invoice->items()->create([
            'item_type' => InvoiceItemType::Posisi,
            'description' => 'SPP bulan '.$periodMonth,
            'amount' => $total,
        ]);

        return $invoice;
    }

    private function fundId(): int
    {
        return (int) Fund::query()->where('code', 'KOM')->value('id');
    }

    private function pay(Student $student, int $amount): Payment
    {
        return app(PaymentService::class)->record(new PaymentSource(
            studentId: $student->getKey(),
            paymentDate: today(),
            method: PaymentMethod::Tunai,
            cashAccountId: (int) CashAccount::query()->first()->getKey(),
            amount: $amount,
            allocations: [],
        ));
    }

    public function test_overdue_issued_invoices_only(): void
    {
        $siswaA = $this->enrolledStudent('A');
        $siswaB = $this->enrolledStudent('B');

        $this->overdueInvoice($siswaA, 15, 600_000);
        $this->overdueInvoice($siswaB, 100, 450_000);

        Invoice::factory()->issued()->dueOn(Carbon::today()->addDays(30))->create([
            'student_id' => $siswaA->getKey(),
            'academic_year_id' => $this->year->getKey(),
            'due_date' => Carbon::today()->addDays(30),
        ]);
        Invoice::factory()->paid()->dueOn(Carbon::today()->subDays(5))->create([
            'student_id' => $siswaA->getKey(),
            'academic_year_id' => $this->year->getKey(),
        ]);

        $rows = $this->service->arrears();

        $this->assertCount(2, $rows);
        $this->assertSame(600_000, $rows[0]['total']);
        $this->assertSame($siswaA->getKey(), $rows[0]['student']->getKey());
        $this->assertSame(450_000, $rows[1]['total']);
        $this->assertSame($siswaB->getKey(), $rows[1]['student']->getKey());
        $this->assertSame('1A', $rows[0]['classroom']);
    }

    public function test_aging_buckets_use_remaining_amount(): void
    {
        $student = $this->enrolledStudent('A');

        $this->overdueInvoice($student, 10, 600_000);
        $this->overdueInvoice($student, 45, 500_000, 200_000, 8);

        $row = $this->service->arrears()->first();

        $this->assertSame([600_000, 300_000, 0, 0], $row['buckets']);
        $this->assertSame(900_000, $row['total']);
        $this->assertSame(45, $row['days_overdue']);
        $this->assertTrue(
            $row['oldest_due']->isSameDay(Carbon::today()->subDays(45)),
        );
    }

    public function test_bucket_boundaries(): void
    {
        $student = $this->enrolledStudent('A');

        $this->overdueInvoice($student, 30, 100_000, periodMonth: 1);
        $this->overdueInvoice($student, 31, 100_000, periodMonth: 2);
        $this->overdueInvoice($student, 60, 100_000, periodMonth: 3);
        $this->overdueInvoice($student, 61, 100_000, periodMonth: 4);
        $this->overdueInvoice($student, 90, 100_000, periodMonth: 5);
        $this->overdueInvoice($student, 91, 100_000, periodMonth: 6);

        $row = $this->service->arrears()->first();

        $this->assertSame([100_000, 200_000, 200_000, 100_000], $row['buckets']);
        $this->assertSame(91, $row['days_overdue']);
    }

    public function test_has_discount_flag(): void
    {
        $siswaBeasiswa = $this->enrolledStudent('A');
        $siswaBiasa = $this->enrolledStudent('B');

        $this->overdueInvoice($siswaBeasiswa, 20, 300_000);
        $this->overdueInvoice($siswaBiasa, 20, 300_000);

        Discount::factory()->create([
            'student_id' => $siswaBeasiswa->getKey(),
            'academic_year_id' => $this->year->getKey(),
            'is_active' => true,
        ]);

        $rows = $this->service->arrears()->keyBy(fn ($row) => $row['student']->getKey());

        $this->assertTrue($rows[$siswaBeasiswa->getKey()]['has_discount']);
        $this->assertFalse($rows[$siswaBiasa->getKey()]['has_discount']);
    }

    public function test_unallocated_payments_reported_then_cleared_by_void(): void
    {
        $student = $this->enrolledStudent('A');
        $this->overdueInvoice($student, 10, 600_000);

        $payment = $this->pay($student, 700_000);

        $unallocated = $this->service->unallocatedPayments();
        $this->assertCount(1, $unallocated);
        $this->assertSame(100_000, $payment->unallocatedAmount());

        app(PaymentService::class)->void($payment, 'Salah input', auth()->user());

        $this->assertCount(0, $this->service->unallocatedPayments());
        $this->assertSame(600_000, (int) $this->service->total());
    }

    public function test_total_sums_all_students(): void
    {
        $siswaA = $this->enrolledStudent('A');
        $siswaB = $this->enrolledStudent('B');

        $this->overdueInvoice($siswaA, 15, 600_000);
        $this->overdueInvoice($siswaB, 40, 450_000, 50_000, 9);

        $this->assertSame(1_000_000, $this->service->total());
    }
}
