<?php

namespace App\Models;

use App\Enums\MaintenanceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenance extends Model
{
    /** @use HasFactory<\Database\Factories\AssetMaintenanceFactory> */
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'maintenance_date',
        'type',
        'description',
        'cost',
        'vendor',
        'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_date' => 'date',
            'type' => MaintenanceType::class,
            'cost' => 'integer',
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
     * JE to 5-1800 (rule #6 path) — null when cost is zero.
     *
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
