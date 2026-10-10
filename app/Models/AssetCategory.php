<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    /** @use HasFactory<\Database\Factories\AssetCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'useful_life_months',
        'asset_account_id',
        'is_depreciable',
    ];

    protected function casts(): array
    {
        return [
            'useful_life_months' => 'integer',
            'is_depreciable' => 'boolean',
        ];
    }

    /**
     * GL account 1-2100…1-2500 the category's assets post to (JE #12).
     *
     * @return BelongsTo<Account, $this>
     */
    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    /**
     * @return HasMany<Asset, $this>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
