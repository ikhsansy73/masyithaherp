<?php

namespace App\Filament\Resources\Penilaian\Pages;

use App\Filament\Resources\Penilaian\PenilaianResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePenilaian extends ManageRecords
{
    protected static string $resource = PenilaianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
