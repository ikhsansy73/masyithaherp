<?php

namespace App\Filament\Resources\Rapor\Pages;

use App\Filament\Resources\Rapor\RaporResource;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewRapor extends ViewRecord
{
    protected static string $resource = RaporResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('student.full_name')
                    ->label('Siswa'),
                TextEntry::make('classroom.name')
                    ->label('Rombel'),
                TextEntry::make('term.name')
                    ->label('Semester'),
                TextEntry::make('status')
                    ->label('Status')
                    ->badge(),
                TextEntry::make('revision_note')
                    ->label('Catatan Revisi')
                    ->placeholder('-'),
                RepeatableEntry::make('subjects')
                    ->label('Nilai Per Mata Pelajaran')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('subject.name')
                            ->label('Mapel'),
                        TextEntry::make('final_score')
                            ->label('Nilai Akhir'),
                        TextEntry::make('predicate')
                            ->label('Predikat')
                            ->badge(),
                        TextEntry::make('description')
                            ->label('Deskripsi'),
                    ])
                    ->columns(4),
            ])
            ->columns(3);
    }
}
