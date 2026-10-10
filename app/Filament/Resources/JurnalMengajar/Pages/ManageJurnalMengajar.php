<?php

namespace App\Filament\Resources\JurnalMengajar\Pages;

use App\Filament\Resources\JurnalMengajar\JurnalMengajarResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageJurnalMengajar extends ManageRecords
{
    protected static string $resource = JurnalMengajarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
