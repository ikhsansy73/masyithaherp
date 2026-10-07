<?php

namespace App\Models;

use App\Enums\InvoiceBatchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceBatch extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceBatchFactory> */
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'fee_type_id',
        'period_month',
        'grade_filter',
        'total_invoices',
        'total_amount',
        'status',
        'generated_by',
        'generated_at',
        'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceBatchStatus::class,
            'period_month' => 'integer',
            'grade_filter' => 'integer',
            'total_invoices' => 'integer',
            'total_amount' => 'integer',
            'generated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * @return BelongsTo<FeeType, $this>
     */
    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * The batch JE posted at Terbitkan (one per batch, not per invoice).
     *
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
