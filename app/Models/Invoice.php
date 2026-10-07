<?php

namespace App\Models;

use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory;

    protected $fillable = [
        'number',
        'student_id',
        'academic_year_id',
        'academic_term_id',
        'invoice_batch_id',
        'invoice_date',
        'due_date',
        'period_month',
        'description',
        'status',
        'total',
        'paid_amount',
        'fund_id',
        'source',
        'voided_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'invoice_date' => 'date',
            'due_date' => 'date',
            'period_month' => 'integer',
            'total' => 'integer',
            'paid_amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * @return BelongsTo<AcademicTerm, $this>
     */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    /**
     * @return BelongsTo<InvoiceBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(InvoiceBatch::class, 'invoice_batch_id');
    }

    /**
     * The invoice fee fund, for payment JE dimensions.
     *
     * @return BelongsTo<Fund, $this>
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * @return HasMany<PaymentAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Gross of discounts: the sum of the posisi lines.
     */
    public function grossAmount(): int
    {
        return (int) $this->items
            ->where('item_type', InvoiceItemType::Posisi)
            ->sum('amount');
    }

    /**
     * Sum of the potongan lines.
     */
    public function discountAmount(): int
    {
        return (int) $this->items
            ->where('item_type', InvoiceItemType::Potongan)
            ->sum('amount');
    }

    /**
     * What is still owed on this invoice.
     */
    public function remainingAmount(): int
    {
        return max(0, $this->total - $this->paid_amount);
    }
}
