<?php

namespace App\Filament\Resources\InventoryItems\Pages;

use App\Filament\Resources\InventoryItems\InventoryItemResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewInventoryItem extends ViewRecord
{
    protected static string $resource = InventoryItemResource::class;

    public function getHeading(): string
    {
        return $this->getRecord()->name;
    }

    public function getTitle(): string
    {
        return 'Item ATK';
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Item')
                ->schema([
                    TextEntry::make('code')->label('Kode'),
                    TextEntry::make('unit')->label('Satuan')->badge(),
                    TextEntry::make('current_stock')->label('Stok Saat Ini')->numeric(2),
                    TextEntry::make('min_stock')->label('Min. Stok')->numeric(2),
                    TextEntry::make('avg_cost')
                        ->label('Harga Rata-rata Tertimbang')
                        ->money('IDR'),
                    TextEntry::make('stock_value')
                        ->label('Nilai Stok (1-1400)')
                        ->state(fn ($record): string => 'Rp '.number_format($record->stockValue(), 0, ',', '.')),
                ])
                ->columns(3),
        ]);
    }
}
