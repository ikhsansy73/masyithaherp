<?php

namespace App\Models;

use App\Enums\JournalSource;
use App\Enums\JournalStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    /** @use HasFactory<\Database\Factories\JournalEntryFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'number',
        'entry_date',
        'accounting_period_id',
        'description',
        'source',
        'reference_type',
        'reference_id',
        'status',
        'voided_at',
        'voided_reason',
        'voided_by_entry_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'source' => JournalSource::class,
            'status' => JournalStatus::class,
            'voided_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Doc 03 §3.3: a posted entry is immutable — application code never
        // deletes one, and corrections go through JournalPostingService::void().
        static::deleting(function (self $entry): void {
            throw new \LogicException('Jurnal yang sudah dibuat tidak dapat dihapus. Gunakan pembatalan (void).');
        });
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', JournalStatus::Posted);
    }

    /**
     * @return BelongsTo<AccountingPeriod, $this>
     */
    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    /**
     * @return HasMany<JournalLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function voidedByEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'voided_by_entry_id');
    }

    /**
     * @return array<int, array{account_id: int, fund_id: int|null, debit: int, credit: int, memo: string|null}>
     */
    public function postedLineAttributes(): array
    {
        return $this->lines()
            ->get(['account_id', 'fund_id', 'debit', 'credit', 'memo'])
            ->map(fn (JournalLine $line): array => [
                'account_id' => $line->account_id,
                'fund_id' => $line->fund_id,
                'debit' => (int) $line->debit,
                'credit' => (int) $line->credit,
                'memo' => $line->memo,
            ])
            ->all();
    }
}
