<?php

namespace App\Filament\Resources\Kurikulum\Pages;

use App\Filament\Resources\Kurikulum\KurikulumResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageKurikulum extends ManageRecords
{
    protected static string $resource = KurikulumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
