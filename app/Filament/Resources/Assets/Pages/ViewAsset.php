<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAsset extends ViewRecord
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AssetResource::duplicateAction(),
            AssetResource::disposalAction(),
        ];
    }

    public function getHeading(): string
    {
        return 'Aset '.$this->getRecord()->code;
    }

    public function getTitle(): string
    {
        return 'Aset';
    }
}
