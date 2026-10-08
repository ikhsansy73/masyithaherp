<?php

namespace Tests\Feature;

use App\Enums\DiscountType;
use App\Enums\EnrollmentStatus;
use App\Enums\InvoiceBatchStatus;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\JournalStatus;
use App\Exceptions\AccountingException;
use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\Classroom;
use App\Models\Discount;
use App\Models\FeeStructure;
use App\Models\FeeType;
use App\Models\Fund;
use App\Models\Student;
use App\Models\User;
use App\Services\Billing\InvoiceBatchService;
use App\Services\School\AcademicYearService;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FundSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceBatchGenerationTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceBatchService $service;

    private AcademicYear $year;

    private Fund $fund;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([FundSeeder::class, AccountSeeder::class]);

        $this->year = app(AcademicYearService::class)->create('2026/2027', isDefault: true);
        $this->fund = Fund::query()->where('code', 'KOM')->sole();

        FeeType::factory()->bulanan()->create([
            'code' => 'SPP',
            'name' => 'SPP (Sumbangan Pembinaan Pendidikan)',
            'revenue_account_id' => Account::query()->where('code', '4-1100')->value('id'),
        ]);

        FeeStructure::factory()->create([
            'academic_year_id' => $this->year->getKey(),
            'fee_type_id' => FeeType::query()->where('code', 'SPP')->sole()->getKey(),
            'grade_level' => null,
            'fund_id' => $this->fund->getKey(),
            'amount' => 600_000,
        ]);

        $this->service = app(InvoiceBatchService::class);
    }

    private function studentWithFee(array $feeAttributes = []): Student
    {
        static $rombel = 0;
        $rombel++;

        $classroom = Classroom::factory()->grade(1, (string) $rombel)->create(['academic_year_id' => $this->year->getKey()]);
        $student = Student::factory()->create();
        $student->enrollments()->create([
            'academic_year_id' => $this->year->getKey(),
            'classroom_id' => $classroom->getKey(),
            'grade_level' => 1,
            'status' => EnrollmentStatus::Aktif,
        ]);
        $student->fees()->create(array_merge([
            'academic_year_id' => $this->year->getKey(),
            'fee_type_id' => FeeType::query()->where('code', 'SPP')->sole()->getKey(),
            'amount' => 600_000,
            'months' => 12,
            'first_month' => 7,
            'is_active' => true,
        ], $feeAttributes));

        return $student;
    }

    public function test_generates_one_draft_invoice_per_eligible_student(): void
    {
        $eligible = $this->studentWithFee();
        $this->studentWithFee(['is_active' => false]);
        $this->studentWithFee(['first_month' => 10]);

        $batch = $this->service->generate(
            year: $this->year,
            feeType: FeeType::query()->where('code', 'SPP')->sole(),
            periodMonth: 7,
        );

        $this->assertNotNull($batch);
        $this->assertSame(1, $batch->total_invoices);
        $this->assertSame(600_000, $batch->total_amount);

        $invoice = $batch->invoices()->sole();
        $this->assertSame($eligible->getKey(), $invoice->student_id);
        $this->assertSame(InvoiceStatus::Draft, $invoice->status);
        $this->assertSame(600_000, $invoice->total);
        $this->assertSame(7, $invoice->period_month);
        $this->assertStringStartsWith('INV/2026-2027/', $invoice->number);
        $this->assertSame($this->fund->getKey(), $invoice->fund_id);

        $item = $invoice->items()->sole();
        $this->assertSame(600_000, $item->amount);
        $this->assertSame(InvoiceItemType::Posisi, $item->item_type);
    }

    public function test_discount_applied_as_potongan_item(): void
    {
        $student = $this->studentWithFee();
        Discount::factory()->create([
            'student_id' => $student->getKey(),
            'academic_year_id' => $this->year->getKey(),
            'fee_type_id' => null,
            'type' => DiscountType::Percent,
            'value' => 10,
            'start_month' => 7,
            'end_month' => 12,
        ]);

        $batch = $this->service->generate(
            year: $this->year,
            feeType: FeeType::query()->where('code', 'SPP')->sole(),
            periodMonth: 7,
        );

        $invoice = $batch->invoices()->sole();
        $this->assertSame(540_000, $invoice->total);

        $potongan = $invoice->items()->where('item_type', 'potongan')->sole();
        $this->assertSame(60_000, $potongan->amount);
    }

    public function test_issue_posts_one_balanced_je_per_batch(): void
    {
        $this->studentWithFee();
        $this->studentWithFee();

        $batch = $this->service->generate(
            year: $this->year,
            feeType: FeeType::query()->where('code', 'SPP')->sole(),
            periodMonth: 7,
        );

        $actor = User::factory()->create();
        $this->service->issue($batch, $actor);

        $batch->refresh();
        $this->assertTrue($batch->status === InvoiceBatchStatus::Issued);
        $this->assertNotNull($batch->journal_entry_id);
        $this->assertSame(2, $batch->total_invoices);
        $this->assertSame(1_200_000, $batch->total_amount);
        $this->assertSame(
            InvoiceStatus::Issued,
            $batch->invoices()->first()->status,
        );

        $entry = $batch->journalEntry;
        $this->assertSame(JournalStatus::Posted, $entry->status);

        $lines = $entry->lines;
        $debit = $lines->where('account_id', $this->receivableId())->sum('debit');
        $credit = $lines->where('account_id', $this->sppRevenueId())->sum('credit');
        $this->assertSame(1_200_000, $debit);
        $this->assertSame(1_200_000, $credit);
        $this->assertSame(
            $lines->sum('debit'),
            $lines->sum('credit'),
        );
    }

    public function test_issue_books_discount_expense_line(): void
    {
        $student = $this->studentWithFee();
        Discount::factory()->create([
            'student_id' => $student->getKey(),
            'academic_year_id' => $this->year->getKey(),
            'type' => DiscountType::Fixed,
            'value' => 100_000,
            'start_month' => 7,
            'end_month' => 12,
        ]);

        $batch = $this->service->generate(
            year: $this->year,
            feeType: FeeType::query()->where('code', 'SPP')->sole(),
            periodMonth: 7,
        );

        $this->service->issue($batch, User::factory()->create());

        $lines = $batch->journalEntry->lines;
        $this->assertSame(100_000, $lines
            ->where('account_id', Account::query()->where('code', '5-1500')->value('id'))
            ->sum('debit'));

        // Net receivable: gross 600k − discount 100k.
        $this->assertSame(500_000, $lines
            ->where('account_id', $this->receivableId())
            ->sum(fn ($line) => $line->debit - $line->credit));
    }

    public function test_regenerate_is_idempotent(): void
    {
        $this->studentWithFee();

        $feeType = FeeType::query()->where('code', 'SPP')->sole();
        $first = $this->service->generate(year: $this->year, feeType: $feeType, periodMonth: 7);
        $second = $this->service->generate(year: $this->year, feeType: $feeType, periodMonth: 7);

        $this->assertSame($first->getKey(), $second->getKey());
        $this->assertSame(1, $second->total_invoices);
        $this->assertSame(1, $second->invoices()->count());
    }

    public function test_cannot_regenerate_issued_batch(): void
    {
        $this->studentWithFee();

        $feeType = FeeType::query()->where('code', 'SPP')->sole();
        $batch = $this->service->generate(year: $this->year, feeType: $feeType, periodMonth: 7);
        $this->service->issue($batch, User::factory()->create());

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('sudah Terbit');

        $this->service->generate(year: $this->year, feeType: $feeType, periodMonth: 7);
    }

    public function test_void_batch_mirrors_je_and_voids_invoices(): void
    {
        $this->studentWithFee();

        $feeType = FeeType::query()->where('code', 'SPP')->sole();
        $batch = $this->service->generate(year: $this->year, feeType: $feeType, periodMonth: 7);
        $this->service->issue($batch, User::factory()->create());
        $originalEntryId = $batch->journal_entry_id;

        $this->service->void($batch, 'Salah bulan', User::factory()->create());

        $batch->refresh();
        $this->assertTrue($batch->status === InvoiceBatchStatus::Void);
        $this->assertSame(InvoiceStatus::Void, $batch->invoices()->first()->status);
        $this->assertNotNull($batch->invoices()->first()->voided_reason);

        $this->assertSame(JournalStatus::Void, $batch->journalEntry->status);

        // Overall books stay balanced after the mirror.
        $this->assertSame(
            \App\Models\JournalLine::query()->sum('debit'),
            \App\Models\JournalLine::query()->sum('credit'),
        );
    }

    public function test_void_batch_blocks_when_invoice_paid(): void
    {
        $student = $this->studentWithFee();

        $feeType = FeeType::query()->where('code', 'SPP')->sole();
        $batch = $this->service->generate(year: $this->year, feeType: $feeType, periodMonth: 7);
        $this->service->issue($batch, User::factory()->create());

        $cashAccount = \App\Models\CashAccount::factory()->create([
            'account_id' => Account::query()->where('code', '1-1100')->value('id'),
        ]);

        $this->actingAs(User::factory()->create());

        app(\App\Services\Billing\PaymentService::class)->record(new \App\Services\Billing\PaymentSource(
            studentId: $student->getKey(),
            paymentDate: today(),
            method: \App\Enums\PaymentMethod::Tunai,
            cashAccountId: $cashAccount->getKey(),
            amount: 600_000,
        ));

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('sudah menerima pembayaran');

        $this->service->void($batch, 'Salah bulan', User::factory()->create());
    }

    private function receivableId(): int
    {
        return (int) Account::query()->where('code', '1-1300')->value('id');
    }

    private function sppRevenueId(): int
    {
        return (int) Account::query()->where('code', '4-1100')->value('id');
    }
}
