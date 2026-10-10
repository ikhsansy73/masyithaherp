<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Master ekstrakurikuler (Pramuka, Marawis, ...). */
class Extracurricular extends Model
{
    /** @use HasFactory<\Database\Factories\ExtracurricularFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ReportCardExtracurricular, $this>
     */
    public function reportCardEntries(): HasMany
    {
        return $this->hasMany(ReportCardExtracurricular::class);
    }
}
