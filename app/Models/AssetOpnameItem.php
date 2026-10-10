<?php

namespace App\Models;

use App\Enums\AssetCondition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetOpnameItem extends Model
{
    /** @use HasFactory<\Database\Factories\AssetOpnameItemFactory> */
    use HasFactory;

    protected $fillable = [
        'asset_opname_id',
        'asset_id',
        'found',
        'condition',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'found' => 'boolean',
            'condition' => AssetCondition::class,
        ];
    }

    /**
     * @return BelongsTo<AssetOpname, $this>
     */
    public function opname(): BelongsTo
    {
        return $this->belongsTo(AssetOpname::class, 'asset_opname_id');
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
