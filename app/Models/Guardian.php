<?php

namespace App\Models;

use App\Enums\GuardianEducation;
use App\Enums\GuardianRelation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guardian extends Model
{
    /** @use HasFactory<\Database\Factories\GuardianFactory> */
    use HasFactory;

    protected $fillable = [
        'student_id',
        'relationship',
        'name',
        'nik',
        'occupation',
        'education',
        'phone',
        'email',
        'is_primary_contact',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'relationship' => GuardianRelation::class,
            'education' => GuardianEducation::class,
            'is_primary_contact' => 'boolean',
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
     * The portal login account (plain relation, not unique — one parent
     * account may guard several students).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
