<?php

namespace App\Models;

use App\Enums\CashAccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashAccount extends Model
{
    /** @use HasFactory<\Database\Factories\CashAccountFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'account_id',
        'bank_name',
        'account_number',
        'is_default_kas',
        'is_default_bank',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => CashAccountType::class,
            'is_default_kas' => 'boolean',
            'is_default_bank' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The GL account this register maps to (1-1100 / 1-1150 / 1-1200).
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
