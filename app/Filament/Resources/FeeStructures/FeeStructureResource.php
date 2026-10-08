<?php

namespace App\Filament\Resources\FeeStructures;

use App\Filament\Resources\FeeStructures\Pages\ManageFeeStructures;
use App\Filament\Resources\FeeTypes\FeeTypeResource;
use App\Models\AcademicYear;
use App\Models\FeeStructure;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class FeeStructureResource extends Resource
{
    protected static ?string $model = FeeStructure::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return 'Struktur Biaya';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Struktur Biaya per Tahun';
    }

    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        return FeeTypeResource::billingFeeResponse($action, $record);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('academic_year_id')
                    ->label('Tahun Ajaran')
                    ->relationship('academicYear', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn (): ?int => AcademicYear::query()->where('is_default', true)->value('id'))
                    ->required(),
                Select::make('fee_type_id')
                    ->label('Jenis Biaya')
                    ->relationship('feeType', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('grade_level')
                    ->label('Tingkat (opsional)')
                    ->options(array_combine(range(1, 6), array_map(
                        fn (int $grade): string => "Kelas {$grade}",
                        range(1, 6),
                    )))
                    ->nullable()
                    ->helperText('Kosong = berlaku untuk semua tingkat; baris per tingkat menimpa baris umum.'),
                Select::make('fund_id')
                    ->label('Bidang Dana')
                    ->relationship('fund', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Kosong = ikut dana bawaan akun pendapatan.'),
                TextInput::make('amount')
                    ->label('Nominal (Rp)')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->prefix('Rp'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('academicYear.name')
                    ->label('Tahun Ajaran')
                    ->sortable(),
                TextColumn::make('feeType.name')
                    ->label('Jenis Biaya')
                    ->searchable(),
                TextColumn::make('grade_level')
                    ->label('Tingkat')
                    ->formatStateUsing(fn ($state): string => $state !== null ? "Kelas {$state}" : 'Semua'),
                TextColumn::make('fund.name')
                    ->label('Bidang Dana')
                    ->placeholder('—'),
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('academic_year_id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFeeStructures::route('/'),
        ];
    }
}
