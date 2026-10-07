<?php

namespace App\Models;

use App\Enums\FeeCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeType extends Model
{
    /** @use HasFactory<\Database\Factories\FeeTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'revenue_account_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'category' => FeeCategory::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function revenueAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'revenue_account_id');
    }

    /**
     * @return HasMany<FeeStructure, $this>
     */
    public function structures(): HasMany
    {
        return $this->hasMany(FeeStructure::class);
    }

    /**
     * @return HasMany<StudentFee, $this>
     */
    public function studentFees(): HasMany
    {
        return $this->hasMany(StudentFee::class);
    }
}
