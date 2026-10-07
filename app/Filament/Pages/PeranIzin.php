<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PeranIzin extends Page
{
    protected string $view = 'filament.pages.peran-izin';

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string | \UnitEnum | null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Peran & Izin';

    protected static ?string $title = 'Peran & Izin';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('system.role.viewAny') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        return [
            'roles' => Role::query()
                ->with('permissions')
                ->withCount(['permissions', 'users'])
                ->orderBy('name')
                ->get(),
            'permissionTotal' => Permission::query()->count(),
        ];
    }
}
