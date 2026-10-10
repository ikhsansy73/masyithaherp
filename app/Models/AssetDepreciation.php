<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDepreciation extends Model
{
    /** @use HasFactory<\Database\Factories\AssetDepreciationFactory> */
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'accounting_period_id',
        'amount',
        'accumulated_amount',
        'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'accumulated_amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return BelongsTo<AccountingPeriod, $this>
     */
    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    /**
     * The per-run JE shared by all lines of the run (rule #13).
     *
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
