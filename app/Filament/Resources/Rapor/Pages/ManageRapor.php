<?php

namespace App\Filament\Resources\Rapor\Pages;

use App\Filament\Resources\Rapor\RaporResource;
use Filament\Resources\Pages\ManageRecords;

class ManageRapor extends ManageRecords
{
    protected static string $resource = RaporResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RaporResource::generateAction(),
        ];
    }
}
