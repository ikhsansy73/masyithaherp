<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Enums\Gender;
use App\Enums\GuardianRelation;
use App\Enums\Religion;
use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Student extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\StudentFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'nis',
        'nisn',
        'nik',
        'full_name',
        'gender',
        'birth_place',
        'birth_date',
        'religion',
        'address',
        'kk_no',
        'akta_no',
        'phone',
        'status',
        'entry_date',
        'exit_date',
        'exit_reason',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'birth_date' => 'date',
            'religion' => Religion::class,
            'status' => StudentStatus::class,
            'entry_date' => 'date',
            'exit_date' => 'date',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')
            ->singleFile();
    }

    /**
     * @return HasMany<Guardian, $this>
     */
    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    /**
     * @return HasMany<StudentEnrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    /**
     * @return HasMany<StudentMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StudentMovement::class);
    }

    /**
     * @return HasMany<StudentAttendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    /**
     * @return HasMany<ReportCard, $this>
     */
    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class);
    }

    /**
     * @return HasMany<StudentFee, $this>
     */
    public function fees(): HasMany
    {
        return $this->hasMany(StudentFee::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<Discount, $this>
     */
    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class);
    }

    /**
     * The enrollment for one academic year (unique per student + year).
     */
    public function enrollmentForYear(int $academicYearId): ?StudentEnrollment
    {
        return $this->enrollments()
            ->where('academic_year_id', $academicYearId)
            ->first();
    }

    /**
     * The guardian receiving financial notices (doc 06 §1).
     */
    public function primaryGuardian(): ?Guardian
    {
        return $this->guardians()
            ->orderByDesc('is_primary_contact')
            ->orderByRaw('field(relationship, ?, ?, ?)', [
                GuardianRelation::Ayah->value,
                GuardianRelation::Ibu->value,
                GuardianRelation::Wali->value,
            ])
            ->first();
    }

    /**
     * Students visible to a portal parent: those with a guardian row
     * linked to the user (doc 09 §4, StudentPolicy).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisibleToParent(Builder $query, User $user): Builder
    {
        return $query->whereHas('guardians', fn (Builder $q) => $q->where('user_id', $user->getKey()));
    }

    /**
     * @return Builder<self>
     */
    public function scopeWhereActiveEnrollmentIn(Builder $query, int $academicYearId): Builder
    {
        return $query->whereHas('enrollments', fn (Builder $q) => $q
            ->where('academic_year_id', $academicYearId)
            ->where('status', EnrollmentStatus::Aktif));
    }
}
