<?php

namespace App\Filament\Widgets;

use App\Enums\StudentStatus;
use App\Models\InventoryItem;
use App\Models\Student;
use App\Services\Billing\ArrearsService;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->can('students.student.viewAny')
            || $user->can('billing.arrears.view')
            || $user->can('assets.asset.viewAny');
    }

    /**
     * Tiles are included per permission, so each role only sees
     * what it is allowed to.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $user = auth()->user();
        $stats = [];

        if ($user?->can('students.student.viewAny')) {
            $active = Student::query()->where('status', StudentStatus::Aktif)->count();

            $stats[] = Stat::make('Siswa Aktif', number_format($active, 0, ',', '.'))
                ->icon(Heroicon::OutlinedUsers)
                ->description('status aktif');
        }

        if ($user?->can('billing.arrears.view')) {
            $total = app(ArrearsService::class)->total();

            $stats[] = Stat::make('Total Tunggakan', 'Rp '.number_format($total, 0, ',', '.'))
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color($total > 0 ? 'danger' : 'success')
                ->description($total > 0 ? 'perlu ditindaklanjuti' : 'tidak ada tunggakan')
                ->descriptionColor($total > 0 ? 'danger' : 'success');
        }

        if ($user?->can('assets.asset.viewAny')) {
            $lowStock = InventoryItem::query()
                ->where('is_active', true)
                ->where('min_stock', '>', 0)
                ->whereColumn('current_stock', '<=', 'min_stock')
                ->count();

            $stats[] = Stat::make('Stok Menipis', number_format($lowStock, 0, ',', '.'))
                ->icon(Heroicon::OutlinedArchiveBox)
                ->description($lowStock > 0 ? 'item di bawah minimum' : 'semua stok aman')
                ->descriptionColor($lowStock > 0 ? 'warning' : 'success');
        }

        return $stats;
    }
}
