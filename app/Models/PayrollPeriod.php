<?php

namespace App\Models;

use App\Enums\PayrollStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    protected $fillable = [
        'name',
        'period_month',
        'period_year',
        'status',
        'total_gross',
        'total_deductions',
        'total_net',
        'calculated_at',
        'approved_at',
        'approved_by',
        'paid_at',
        'journal_entry_id',
        'payment_journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => PayrollStatus::class,
            'period_month' => 'integer',
            'period_year' => 'integer',
            'total_gross' => 'integer',
            'total_deductions' => 'integer',
            'total_net' => 'integer',
            'calculated_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Payslip, $this>
     */
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    /**
     * JE #9 — the accrual posted on approval.
     *
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * JE #10 — the payment posted when the period is paid out.
     *
     * @return BelongsTo<JournalEntry, $this>
     */
    public function paymentJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * First/last day of the payroll month — the attendance window.
     */
    public function startsAt(): \Illuminate\Support\Carbon
    {
        return \Illuminate\Support\Carbon::create($this->period_year, $this->period_month, 1)->startOfDay();
    }

    public function endsAt(): \Illuminate\Support\Carbon
    {
        return $this->startsAt()->copy()->endOfMonth();
    }

    /**
     * "Juli 2026" style label for headings and journal descriptions.
     */
    public function monthLabel(): string
    {
        return $this->startsAt()->locale(config('app.locale'))->translatedFormat('F Y');
    }
}
