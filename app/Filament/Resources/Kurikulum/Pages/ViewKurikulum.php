<?php

namespace App\Filament\Resources\Kurikulum\Pages;

use App\Filament\Resources\Kurikulum\KurikulumResource;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewKurikulum extends ViewRecord
{
    protected static string $resource = KurikulumResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->elemen.' — '.$this->getRecord()->subject?->name;
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('subject.name')->label('Mata Pelajaran'),
            TextEntry::make('fase')->label('Fase')->badge(),
            TextEntry::make('elemen')->label('Elemen'),
            TextEntry::make('code')->label('Kode CP'),
            TextEntry::make('description')->label('Deskripsi CP'),
            IconEntry::make('is_active')->label('Aktif')->boolean(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
