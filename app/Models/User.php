<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Staff profile when this user is linked to an employee record.
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Staff roles land on /admin, parents (wali murid) on /portal.
     * Deactivated users are rejected on both panels.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->hasAnyRole([
                'super_admin',
                'kepala_sekolah',
                'bendahara',
                'operator_tu',
                'guru',
                'wali_kelas',
            ]),
            'portal' => $this->hasRole('wali_murid'),
            default => false,
        };
    }
}
