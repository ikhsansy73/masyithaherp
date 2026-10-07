<?php

namespace App\Models;

use App\Enums\SubjectKelompok;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    /** @use HasFactory<\Database\Factories\SubjectFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'kelompok',
        'jp_per_week',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'kelompok' => SubjectKelompok::class,
            'jp_per_week' => 'integer',
            'is_active' => 'boolean',
        ];
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
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
