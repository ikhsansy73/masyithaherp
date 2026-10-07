<?php

namespace App\Filament\Resources\StudentMovements\Pages;

use App\Filament\Resources\StudentMovements\StudentMovementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStudentMovements extends ManageRecords
{
    protected static string $resource = StudentMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
