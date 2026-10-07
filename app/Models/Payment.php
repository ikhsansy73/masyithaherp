<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'number',
        'student_id',
        'payment_date',
        'method',
        'cash_account_id',
        'amount',
        'reference',
        'received_by',
        'notes',
        'journal_entry_id',
        'reversed_by_payment_id',
    ];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'payment_date' => 'date',
            'amount' => 'integer',
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
     * The register that received the money.
     *
     * @return BelongsTo<CashAccount, $this>
     */
    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * The posted payment JE (posting rule #3).
     *
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * On the original payment: the reversal row created at void.
     *
     * @return BelongsTo<Payment, $this>
     */
    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'reversed_by_payment_id');
    }

    /**
     * @return HasMany<PaymentAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Amount recorded but not allocated to any invoice; surfaces in the
     * "Pembayaran belum dialokasikan" widget (doc 04 §4).
     */
    public function unallocatedAmount(): int
    {
        return $this->amount - (int) $this->allocations->sum('amount');
    }
}
