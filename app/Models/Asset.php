<?php

namespace App\Models;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\DisposalMethod;
use App\Enums\FundingSource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    /** @use HasFactory<\Database\Factories\AssetFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'asset_category_id',
        'acquisition_date',
        'acquisition_cost',
        'fund_id',
        'funding_source',
        'brand_model',
        'serial_no',
        'condition',
        'location_id',
        'custodian_id',
        'status',
        'useful_life_months',
        'salvage_value',
        'disposal_date',
        'disposal_method',
        'disposal_proceeds',
        'journal_entry_id',
        'disposal_journal_entry_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'acquisition_cost' => 'integer',
            'funding_source' => FundingSource::class,
            'condition' => AssetCondition::class,
            'status' => AssetStatus::class,
            'useful_life_months' => 'integer',
            'salvage_value' => 'integer',
            'disposal_date' => 'date',
            'disposal_method' => DisposalMethod::class,
            'disposal_proceeds' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AssetCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    /**
     * @return BelongsTo<Fund, $this>
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Penanggung jawab.
     *
     * @return BelongsTo<Employee, $this>
     */
    public function custodian(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'custodian_id');
    }

    /**
     * JE #12 — posted at acquisition.
     *
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * JE #14/#15 — posted at disposal.
     *
     * @return BelongsTo<JournalEntry, $this>
     */
    public function disposalJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * @return HasMany<AssetDepreciation, $this>
     */
    public function depreciations(): HasMany
    {
        return $this->hasMany(AssetDepreciation::class);
    }

    /**
     * @return HasMany<AssetMaintenance, $this>
     */
    public function maintenances(): HasMany
    {
        return $this->hasMany(AssetMaintenance::class);
    }

    /**
     * @return HasMany<AssetOpnameItem, $this>
     */
    public function opnameItems(): HasMany
    {
        return $this->hasMany(AssetOpnameItem::class);
    }

    /**
     * Masa manfaat efektif: override aset, jika tidak ada default
     * kelompoknya (doc 02 §7).
     */
    public function effectiveUsefulLifeMonths(): ?int
    {
        return $this->useful_life_months ?? $this->category->useful_life_months;
    }

    /**
     * Σ penyusutan yang sudah dibukukan.
     */
    public function accumulatedDepreciation(): int
    {
        return (int) $this->depreciations()->sum('amount');
    }

    /**
     * Nilai buku: biaya perolehan − akumulasi penyusutan.
     */
    public function netBookValue(): int
    {
        return $this->acquisition_cost - $this->accumulatedDepreciation();
    }

    /**
     * Sisa nilai yang masih boleh disusutkan: NBV − nilai sisa.
     */
    public function remainingDepreciableAmount(): int
    {
        return max(0, $this->netBookValue() - $this->salvage_value);
    }
}
