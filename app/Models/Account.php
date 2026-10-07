<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\CashFlowCategory;
use App\Enums\NormalBalance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    /** @use HasFactory<\Database\Factories\AccountFactory> */
    use HasFactory;

    /** GL accounts that represent the cash pockets of the school (doc 03 §6.5). */
    public const CASH_ACCOUNT_CODES = ['1-1100', '1-1150', '1-1200'];

    protected $fillable = [
        'code',
        'name',
        'type',
        'normal_balance',
        'is_header',
        'parent_id',
        'cash_flow_category',
        'default_fund_id',
        'is_locked',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'normal_balance' => NormalBalance::class,
            'is_header' => 'boolean',
            'cash_flow_category' => CashFlowCategory::class,
            'is_locked' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** A postable account is active and not a header/group row. */
    public function isPostable(): bool
    {
        return $this->is_active && ! $this->is_header;
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * @return HasMany<Account, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id')->orderBy('code');
    }

    /**
     * @return HasMany<JournalLine, $this>
     */
    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    /**
     * @return BelongsTo<Fund, $this>
     */
    public function defaultFund(): BelongsTo
    {
        return $this->belongsTo(Fund::class, 'default_fund_id');
    }
}
