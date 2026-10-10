<?php

namespace App\Models;

use App\Enums\ReportCardStatus;
use App\Models\Concerns\ScopedToOwnClassrooms;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rapor per student per term (doc 06 §7). The parent portal shows this
 * row only once status = diterbitkan (published_at set).
 */
class ReportCard extends Model
{
    /** @use HasFactory<\Database\Factories\ReportCardFactory> */
    use HasFactory, ScopedToOwnClassrooms;

    protected $fillable = [
        'student_id',
        'classroom_id',
        'academic_term_id',
        'status',
        'revision_note',
        'catatan_wali_kelas',
        'days_sick',
        'days_izin',
        'days_alpa',
        'height_cm',
        'weight_kg',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'approved_by',
        'published_at',
        'published_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReportCardStatus::class,
            'days_sick' => 'integer',
            'days_izin' => 'integer',
            'days_alpa' => 'integer',
            'height_cm' => 'decimal:1',
            'weight_kg' => 'decimal:1',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<Classroom, $this>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * @return BelongsTo<AcademicTerm, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'academic_term_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * @return HasMany<ReportCardSubject, $this>
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(ReportCardSubject::class);
    }

    /**
     * @return HasMany<ReportCardExtracurricular, $this>
     */
    public function extracurriculars(): HasMany
    {
        return $this->hasMany(ReportCardExtracurricular::class);
    }

    /**
     * @return HasMany<ReportCardAchievement, $this>
     */
    public function achievements(): HasMany
    {
        return $this->hasMany(ReportCardAchievement::class);
    }

    /**
     * Portal readiness (doc 06 §7): parents may see only published rapor.
     */
    public function isPublished(): bool
    {
        return $this->status === ReportCardStatus::Diterbitkan
            && $this->published_at !== null;
    }
}
