<?php

namespace Tests\Feature;

use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\JournalStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\AccountingException;
use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\CashAccount;
use App\Models\Fund;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Services\Billing\PaymentService;
use App\Services\Billing\PaymentSource;
use App\Services\School\AcademicYearService;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FundSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PaymentAllocationTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $service;

    private CashAccount $cash;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([FundSeeder::class, AccountSeeder::class]);
        $this->year = app(AcademicYearService::class)->create('2026/2027', isDefault: true);

        $this->cash = CashAccount::factory()->create([
            'account_id' => Account::query()->where('code', '1-1100')->value('id'),
        ]);

        $this->service = app(PaymentService::class);
        $this->actingAs(User::factory()->create());
    }

    /**
     * An issued invoice with a posisi line matching the total — the shape
     * the batch service leaves behind after Terbitkan.
     */
    private function issuedInvoice(array $attributes = []): Invoice
    {
        $invoice = Invoice::factory()->issued()->create(array_merge([
            'academic_year_id' => $this->year->getKey(),
            'fund_id' => $this->fundId(),
            'due_date' => Carbon::create(2026, 7, 10),
        ], $attributes));

        $invoice->items()->create([
            'item_type' => InvoiceItemType::Posisi,
            'description' => 'SPP',
            'amount' => $invoice->total,
        ]);

        return $invoice;
    }

    /**
     * @param  array<int, int>  $allocations  invoice_id => amount
     */
    private function pay(Student|int $student, int $amount, array $allocations = []): Payment
    {
        return $this->service->record(new PaymentSource(
            studentId: $student instanceof Student ? $student->getKey() : $student,
            paymentDate: today(),
            method: PaymentMethod::Tunai,
            cashAccountId: $this->cash->getKey(),
            amount: $amount,
            allocations: $allocations,
        ));
    }

    private function fundId(): int
    {
        return (int) Fund::query()->where('code', 'KOM')->value('id');
    }

    public function test_fifo_prefills_oldest_due_first(): void
    {
        $student = Student::factory()->create();
        $older = $this->issuedInvoice(['student_id' => $student->getKey(), 'due_date' => Carbon::create(2026, 7, 10)]);
        $newer = $this->issuedInvoice(['student_id' => $student->getKey(), 'due_date' => Carbon::create(2026, 8, 10)]);

        $payment = $this->pay($student, 700_000);

        $this->assertSame(600_000, (int) $payment->allocations()->where('invoice_id', $older->getKey())->sum('amount'));
        $this->assertSame(100_000, (int) $payment->allocations()->where('invoice_id', $newer->getKey())->sum('amount'));
        $this->assertSame(InvoiceStatus::Paid, $older->refresh()->status);
        $this->assertSame(InvoiceStatus::PartiallyPaid, $newer->refresh()->status);
        $this->assertSame(100_000, $newer->paid_amount);
    }

    public function test_manual_override_targets_chosen_invoices(): void
    {
        $student = Student::factory()->create();
        $older = $this->issuedInvoice(['student_id' => $student->getKey(), 'due_date' => Carbon::create(2026, 7, 10)]);
        $newer = $this->issuedInvoice(['student_id' => $student->getKey(), 'due_date' => Carbon::create(2026, 8, 10)]);

        $payment = $this->pay($student, 600_000, [$newer->getKey() => 600_000]);

        $this->assertSame(600_000, (int) $payment->allocations()->where('invoice_id', $newer->getKey())->sum('amount'));
        $this->assertSame(0, (int) $payment->allocations()->where('invoice_id', $older->getKey())->sum('amount'));
        $this->assertSame(InvoiceStatus::Issued, $older->refresh()->status);
        $this->assertSame(InvoiceStatus::Paid, $newer->refresh()->status);
    }

    public function test_partial_payment_marks_invoice_partially_paid(): void
    {
        $invoice = $this->issuedInvoice();

        $payment = $this->pay($invoice->student_id, 300_000);

        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->refresh()->status);
        $this->assertSame(300_000, $invoice->paid_amount);
        $this->assertSame(300_000, $invoice->remainingAmount());
    }

    public function test_overpayment_per_invoice_blocked(): void
    {
        $invoice = $this->issuedInvoice();

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Pembayaran melebihi sisa tagihan.');

        $this->pay($invoice->student_id, 700_000, [$invoice->getKey() => 700_000]);
    }

    public function test_cannot_pay_draft_or_void_invoice(): void
    {
        $student = Student::factory()->create();
        $draft = $this->issuedInvoice(['student_id' => $student->getKey(), 'status' => InvoiceStatus::Draft]);
        $void = $this->issuedInvoice(['student_id' => $student->getKey(), 'status' => InvoiceStatus::Void]);

        try {
            $this->pay($student, 100_000, [$draft->getKey() => 100_000]);
            $this->fail('Draft invoice accepted a payment.');
        } catch (AccountingException $exception) {
            $this->assertSame('Tagihan '.$draft->number.' berstatus Draft tidak dapat dibayar.', $exception->getMessage());
        }

        try {
            $this->pay($student, 100_000, [$void->getKey() => 100_000]);
            $this->fail('Void invoice accepted a payment.');
        } catch (AccountingException $exception) {
            $this->assertSame('Tagihan '.$void->number.' berstatus '.$void->status->label().' tidak dapat dibayar.', $exception->getMessage());
        }
    }

    public function test_allocation_total_cannot_exceed_payment(): void
    {
        $invoice = $this->issuedInvoice();

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Total alokasi melebihi jumlah pembayaran.');

        $this->pay($invoice->student_id, 300_000, [$invoice->getKey() => 400_000]);
    }

    public function test_unallocated_remainder_posts_to_2_1500(): void
    {
        $invoice = $this->issuedInvoice();

        $payment = $this->pay($invoice->student_id, 650_000);

        $lines = $payment->journalEntry->lines;
        $this->assertSame(650_000, $lines
            ->where('account_id', $this->accountId('1-1100'))
            ->sum('debit'));
        $this->assertSame(600_000, $lines
            ->where('account_id', $this->accountId('1-1300'))
            ->sum('credit'));
        $this->assertSame(50_000, $lines
            ->where('account_id', $this->accountId('2-1500'))
            ->sum('credit'));
        $this->assertSame('Penerimaan belum dialokasikan', $lines
            ->where('account_id', $this->accountId('2-1500'))
            ->first()->memo);
        $this->assertSame($lines->sum('debit'), $lines->sum('credit'));
    }

    public function test_void_payment_mirrors_je_and_restores_invoices(): void
    {
        $student = Student::factory()->create();
        $older = $this->issuedInvoice(['student_id' => $student->getKey(), 'due_date' => Carbon::create(2026, 7, 10)]);
        $newer = $this->issuedInvoice(['student_id' => $student->getKey(), 'due_date' => Carbon::create(2026, 8, 10)]);

        $payment = $this->pay($student, 700_000);

        $this->assertSame(InvoiceStatus::Paid, $older->refresh()->status);

        $actor = User::factory()->create();
        $reversal = $this->service->void($payment, 'Salah input kasir', $actor);

        $this->assertSame(-700_000, $reversal->amount);
        $this->assertStringStartsWith('KW/', $reversal->number);
        $this->assertNotSame($payment->refresh()->number, $reversal->number);
        $this->assertSame($reversal->getKey(), $payment->reversed_by_payment_id);
        $this->assertSame(0, $payment->allocations()->count());
        $this->assertStringContainsString('Salah input kasir', $reversal->notes);

        // Both invoices lose this kwitansi's allocations entirely.
        $this->assertSame(InvoiceStatus::Issued, $older->refresh()->status);
        $this->assertSame(0, $older->paid_amount);
        $this->assertSame(InvoiceStatus::Issued, $newer->refresh()->status);
        $this->assertSame(0, $newer->paid_amount);

        $this->assertSame(JournalStatus::Void, $payment->journalEntry->status);
        $this->assertSame(JournalStatus::Posted, $reversal->journalEntry->status);
    }

    public function test_double_void_blocked(): void
    {
        $invoice = $this->issuedInvoice();
        $payment = $this->pay($invoice->student_id, 600_000);
        $this->service->void($payment, 'Salah input kasir', User::factory()->create());

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Kwitansi sudah dibatalkan.');

        $this->service->void($payment, 'Lagi', User::factory()->create());
    }

    public function test_kwitansi_numbers_are_not_reused(): void
    {
        $invoice = $this->issuedInvoice();
        $first = $this->pay($invoice->student_id, 100_000);
        $this->service->void($first, 'Salah input kasir', User::factory()->create());
        $second = $this->pay($invoice->student_id, 100_000);

        $this->assertNotSame($first->refresh()->number, $second->number);
        $this->assertSame(3, Payment::query()->count());
    }

    private function accountId(string $code): int
    {
        return (int) Account::query()->where('code', $code)->value('id');
    }
}
