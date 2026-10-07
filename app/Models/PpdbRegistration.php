<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\PpdbStatus;
use App\Enums\Religion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpdbRegistration extends Model
{
    /** @use HasFactory<\Database\Factories\PpdbRegistrationFactory> */
    use HasFactory;

    protected $fillable = [
        'registration_no',
        'academic_year_id',
        'applicant_name',
        'gender',
        'birth_place',
        'birth_date',
        'religion',
        'nik',
        'origin_tk',
        'address',
        'father_name',
        'mother_name',
        'parent_phone',
        'status',
        'registered_at',
        'verified_by',
        'converted_student_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'birth_date' => 'date',
            'religion' => Religion::class,
            'status' => PpdbStatus::class,
            'registered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function convertedStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'converted_student_id');
    }
}
