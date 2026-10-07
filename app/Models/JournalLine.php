<?php

namespace App\Models;

use App\Enums\JournalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    /** @use HasFactory<\Database\Factories\JournalLineFactory> */
    use HasFactory;

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'fund_id',
        'debit',
        'credit',
        'memo',
    ];

    protected static function booted(): void
    {
        // Doc 03 §3.3: once the entry is posted, its lines never change via
        // application code. Corrections are mirror voids on the entry.
        static::updating(function (self $line): void {
            self::guardPosted($line);
        });

        static::deleting(function (self $line): void {
            self::guardPosted($line);
        });
    }

    private static function guardPosted(self $line): void
    {
        $entry = $line->journalEntry;

        if ($entry !== null && $entry->status === JournalStatus::Posted) {
            throw new \LogicException('Baris jurnal yang sudah diposting tidak dapat diubah atau dihapus.');
        }
    }

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<Fund, $this>
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }
}
