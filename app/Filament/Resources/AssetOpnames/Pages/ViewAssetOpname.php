<?php

namespace App\Filament\Resources\AssetOpnames\Pages;

use App\Filament\Resources\AssetOpnames\AssetOpnameResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAssetOpname extends ViewRecord
{
    protected static string $resource = AssetOpnameResource::class;

    public function getHeading(): string
    {
        return $this->getRecord()->name;
    }

    public function getTitle(): string
    {
        return 'Opname';
    }
}
