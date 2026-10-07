<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Accounting core per docs/design/02-database-schema.md §2.
     * The per-entry balance invariant is layered: service validation (primary)
     * plus this CHECK constraint on journal_lines (database level).
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->char('code', 8)->unique()->comment('e.g. 1-1100');
            $table->string('name');
            $table->string('type')->comment('Enum: AccountType (aset/kewajiban/ekuitas/pendapatan/beban)');
            $table->string('normal_balance', 8)->comment('Enum: NormalBalance (debit/kredit)');
            $table->boolean('is_header')->default(false)->comment('Header/group row — never postable');
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('cash_flow_category', 16)->nullable()->comment('Enum: CashFlowCategory (operasi/investasi/pendanaan/non_kas)');
            $table->foreignId('default_fund_id')->nullable()->constrained('funds')->nullOnDelete();
            $table->boolean('is_locked')->default(false)->comment('System accounts cannot be edited/deleted');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique()->comment('JE/2026-07/000318');
            $table->date('entry_date');
            $table->foreignId('accounting_period_id')->constrained();
            $table->string('description');
            $table->string('source', 16)->comment('Enum: JournalSource (manual/otomatis)');
            $table->nullableMorphs('reference');
            $table->string('status', 16)->comment('Enum: JournalStatus (posted/void)');
            $table->timestamp('voided_at')->nullable();
            $table->string('voided_reason')->nullable();
            $table->foreignId('voided_by_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->softDeletes();
            $table->timestamps();

            $table->index('entry_date');
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->foreignId('fund_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('debit')->default(0);
            $table->unsignedBigInteger('credit')->default(0);
            $table->string('memo')->nullable();
            $table->timestamps();

            $table->index('account_id');
            $table->index('fund_id');
        });

        DB::statement(
            'ALTER TABLE journal_lines ADD CONSTRAINT chk_journal_lines_signs '
            .'CHECK (debit >= 0 AND credit >= 0 AND NOT (debit > 0 AND credit > 0))',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
    }
};
