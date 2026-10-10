<?php

namespace App\Models;

use App\Enums\SalaryCalculation;
use App\Enums\SalaryComponentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryComponent extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'calculation',
        'default_amount',
        'percent_rate',
        'is_employer',
        'gl_account_id',
        'liability_account_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => SalaryComponentType::class,
            'calculation' => SalaryCalculation::class,
            'default_amount' => 'integer',
            'percent_rate' => 'decimal:4',
            'is_employer' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Expense account for earnings (5-11xx); null on potongan rows.
     *
     * @return BelongsTo<Account, $this>
     */
    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gl_account_id');
    }

    /**
     * Utang account for deductions (2-11xx/2-12xx/2-13xx); also the
     * credit target of employer BPJS lines on JE #9.
     *
     * @return BelongsTo<Account, $this>
     */
    public function liabilityAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'liability_account_id');
    }

    /**
     * @return HasMany<EmployeeSalaryComponent, $this>
     */
    public function employeeComponents(): HasMany
    {
        return $this->hasMany(EmployeeSalaryComponent::class);
    }
}
