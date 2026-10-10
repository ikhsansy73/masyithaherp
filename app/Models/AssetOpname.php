<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetOpname extends Model
{
    /** @use HasFactory<\Database\Factories\AssetOpnameFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'opname_date',
        'conducted_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opname_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function conductedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conducted_by');
    }

    /**
     * @return HasMany<AssetOpnameItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(AssetOpnameItem::class);
    }
}
