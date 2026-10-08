<?php

namespace App\Filament\Widgets;

use App\Services\Billing\ArrearsService;
use Filament\Widgets\Widget;

class TunggakanTeratasWidget extends Widget
{
    protected string $view = 'filament.widgets.tunggakan-teratas';

    protected int|string|array $columnSpan = 'full';

    public function canAccess(): bool
    {
        return auth()->user()?->can('billing.arrears.view') ?? false;
    }

    /**
     * Top 10 students by outstanding amount.
     */
    public function topArrears(): \Illuminate\Support\Collection
    {
        return app(ArrearsService::class)->arrears()->take(10);
    }
}
