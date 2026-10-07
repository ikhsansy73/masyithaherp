<?php

namespace App\Filament\Resources\CalendarDays\Pages;

use App\Filament\Resources\CalendarDays\CalendarDayResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCalendarDays extends ManageRecords
{
    protected static string $resource = CalendarDayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
