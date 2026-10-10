<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'base_salary',
        'total_earnings',
        'total_deductions',
        'net_salary',
        'days_present',
        'days_sick',
        'days_leave',
        'days_absent',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'integer',
            'total_earnings' => 'integer',
            'total_deductions' => 'integer',
            'net_salary' => 'integer',
            'days_present' => 'integer',
            'days_sick' => 'integer',
            'days_leave' => 'integer',
            'days_absent' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<PayrollPeriod, $this>
     */
    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return HasMany<PayslipItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PayslipItem::class);
    }

    /**
     * @return HasMany<PayslipItem, $this>
     */
    public function earnings(): HasMany
    {
        return $this->items()->where('type', 'pendapatan');
    }

    /**
     * @return HasMany<PayslipItem, $this>
     */
    public function deductions(): HasMany
    {
        return $this->items()->where('type', 'potongan');
    }
}
