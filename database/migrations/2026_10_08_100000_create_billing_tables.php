<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Doc 02 §3 billing & payments tables. Fund dimension lives on
     * invoices/invoice_items (added beyond doc 02's original columns,
     * updated in the same change) so batch and payment journal entries
     * can carry the fee fund from the source document.
     */
    public function up(): void
    {
        Schema::create('cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('"Kas Sekolah", "BCA 1234567890"');
            $table->string('type')->comment('Enum: CashAccountType (kas/bank)');
            $table->foreignId('account_id')->unique()->constrained('accounts')->restrictOnDelete()->comment('GL account 1-1100/1-1150/1-1200');
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->boolean('is_default_kas')->default(false);
            $table->boolean('is_default_bank')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_type_id')->nullable()->constrained()->nullOnDelete()->comment('null = all fee types');
            $table->string('name')->comment('"Beasiswa Prestasi", "Potongan Anak Guru"');
            $table->string('type')->comment('Enum: DiscountType (percent/fixed)');
            $table->unsignedBigInteger('value')->comment('Percent 0-100 or fixed rupiah per invoice');
            $table->unsignedTinyInteger('start_month');
            $table->unsignedTinyInteger('end_month');
            $table->boolean('is_active')->default(true);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('student_id');
        });

        Schema::create('invoice_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_type_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('period_month')->nullable()->comment('Null for one-off (tahunan/insidental) batches');
            $table->unsignedTinyInteger('grade_filter')->nullable();
            $table->unsignedInteger('total_invoices')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->string('status')->comment('Enum: InvoiceBatchStatus (draft/issued/void)');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete()->comment('Posted at Terbitkan');
            $table->timestamps();

            $table->unique(['academic_year_id', 'fee_type_id', 'period_month']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique()->comment('INV/2026-2027/000123');
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_term_id')->nullable()->constrained('academic_terms')->nullOnDelete();
            $table->foreignId('invoice_batch_id')->nullable()->constrained('invoice_batches')->cascadeOnDelete();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->unsignedTinyInteger('period_month')->nullable()->comment('SPP calendar month 1-12; null for insidental');
            $table->string('description');
            $table->string('status')->comment('Enum: InvoiceStatus (draft/issued/partially_paid/paid/void/cancelled)');
            $table->unsignedBigInteger('total');
            $table->unsignedBigInteger('paid_amount')->default(0)->comment('Derived cache, written only by PaymentService');
            $table->foreignId('fund_id')->nullable()->constrained('funds')->nullOnDelete()->comment('The invoice fee fund, for JE dimensions');
            $table->string('source')->comment('batch / manual');
            $table->string('voided_reason')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status', 'due_date']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete()->comment('Draft only; issued invoices are immutable');
            $table->string('item_type')->comment('Enum: InvoiceItemType (posisi charge / potongan discount)');
            $table->foreignId('fee_type_id')->nullable()->constrained('fee_types')->nullOnDelete()->comment('On posisi');
            $table->foreignId('discount_id')->nullable()->constrained('discounts')->nullOnDelete()->comment('On potongan');
            $table->string('description');
            $table->unsignedBigInteger('amount')->comment('Positive; sign comes from item_type');
            $table->foreignId('revenue_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('Potongan → expense account 5-1500');
            $table->foreignId('fund_id')->nullable()->constrained('funds')->nullOnDelete()->comment('Fee fund for the batch JE');
            $table->timestamps();

            $table->index('invoice_id');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique()->comment('Kwitansi KW/2026/000042, never reused');
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('payment_date');
            $table->string('method')->comment('Enum: PaymentMethod (tunai/transfer/qris/ewallet/lainnya)');
            $table->foreignId('cash_account_id')->constrained('cash_accounts')->restrictOnDelete();
            $table->unsignedBigInteger('amount')->comment('Total received');
            $table->string('reference')->nullable()->comment('Reserved for future payment-gateway reference');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('reversed_by_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'payment_date']);
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['payment_id', 'invoice_id']);
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_batches');
        Schema::dropIfExists('discounts');
        Schema::dropIfExists('cash_accounts');
    }
};
