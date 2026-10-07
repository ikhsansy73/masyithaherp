<?php

namespace App\Filament\Resources\AcademicYears;

use App\Enums\AcademicYearStatus;
use App\Filament\Resources\AcademicYears\Pages\ManageAcademicYears;
use App\Models\AcademicYear;
use App\Services\School\AcademicYearService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Model;

class AcademicYearResource extends Resource
{
    protected static ?string $model = AcademicYear::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string | \UnitEnum | null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Tahun Ajaran';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Tahun Ajaran';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('academics.year.viewAny') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('academics.year.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('academics.year.update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('academics.year.delete') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Tahun Ajaran')
                    ->placeholder('2026/2027')
                    ->required()
                    ->maxLength(9)
                    ->regex('/^\d{4}\/\d{4}$/', 'Format harus 2026/2027.')
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit')
                    ->helperText('Format: 2026/2027 — diawali bulan Juli.'),
                Toggle::make('is_default')
                    ->label('Jadikan Tahun Ajaran Default')
                    ->live()
                    ->disabled(fn (?AcademicYear $record): bool => (bool) ($record?->is_default))
                    ->helperText('Hanya satu tahun ajaran default. Mengaktifkan ini akan mengalihkan default dari tahun ajaran lain.'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Tahun Ajaran')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => match ($state) {
                        AcademicYearStatus::Planned => Color::Gray,
                        AcademicYearStatus::Active => Color::Emerald,
                        AcademicYearStatus::Closed => Color::Red,
                    }),
                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
                TextColumn::make('terms_count')
                    ->counts('terms')
                    ->label('Semester'),
                TextColumn::make('periods_count')
                    ->counts('periods')
                    ->label('Periode Akuntansi'),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(fn (AcademicYear $record) => $record->is_default
                        ? app(AcademicYearService::class)->makeDefault($record)
                        : null),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAcademicYears::route('/'),
        ];
    }
}
