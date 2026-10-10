<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_no',
        'name',
        'nik',
        'nip',
        'gender',
        'birth_place',
        'birth_date',
        'address',
        'phone',
        'position',
        'employment_status',
        'is_teaching',
        'join_date',
        'end_date',
        'marital_status',
        'bank_name',
        'bank_account_no',
        'bpjs_kesehatan_no',
        'bpjs_ketenagakerjaan_no',
        'npwp_no',
        'base_salary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'birth_date' => 'date',
            'employment_status' => EmploymentStatus::class,
            'is_teaching' => 'boolean',
            'join_date' => 'date',
            'end_date' => 'date',
            'base_salary' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Classrooms where this employee is wali kelas.
     *
     * @return HasMany<Classroom, $this>
     */
    public function homeroomClassrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'homeroom_teacher_id');
    }

    /**
     * @return HasMany<ClassSubjectTeacher, $this>
     */
    public function classSubjectTeachers(): HasMany
    {
        return $this->hasMany(ClassSubjectTeacher::class);
    }

    /**
     * @return HasMany<Schedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Per-employee salary configuration (amount/rate per component).
     *
     * @return HasMany<EmployeeSalaryComponent, $this>
     */
    public function salaryComponents(): HasMany
    {
        return $this->hasMany(EmployeeSalaryComponent::class);
    }

    /**
     * @return HasMany<Payslip, $this>
     */
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    /**
     * @return HasMany<EmployeeAttendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class);
    }

    /**
     * @return HasMany<Leave, $this>
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeTeaching(Builder $query): Builder
    {
        return $query->where('is_teaching', true)->where('is_active', true);
    }
}
