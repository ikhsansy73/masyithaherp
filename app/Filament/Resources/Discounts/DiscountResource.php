<?php

namespace App\Filament\Resources\Discounts;

use App\Enums\DiscountType;
use App\Filament\Resources\Discounts\Pages\ManageDiscounts;
use App\Filament\Resources\FeeTypes\FeeTypeResource;
use App\Models\AcademicYear;
use App\Models\Discount;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 7;

    public static function getModelLabel(): string
    {
        return 'Potongan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Beasiswa & Potongan';
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
                Select::make('student_id')
                    ->label('Siswa')
                    ->relationship('student', 'full_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('academic_year_id')
                    ->label('Tahun Ajaran')
                    ->relationship('academicYear', 'name')
                    ->default(fn (): ?int => AcademicYear::query()->where('is_default', true)->value('id'))
                    ->required(),
                Select::make('fee_type_id')
                    ->label('Jenis Biaya')
                    ->relationship('feeType', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Kosong = semua jenis biaya bulanan.'),
                TextInput::make('name')
                    ->label('Keterangan')
                    ->required()
                    ->maxLength(255)
                    ->default('Potongan SPP'),
                Select::make('type')
                    ->label('Jenis Potongan')
                    ->options([
                        'percent' => 'Persen (%)',
                        'fixed' => 'Nominal (Rp)',
                    ])
                    ->required(),
                TextInput::make('value')
                    ->label('Nilai')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->helperText('Persen (0-100) atau nominal rupiah.'),
                Select::make('start_month')
                    ->label('Mulai Bulan')
                    ->options([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'])
                    ->default(7)
                    ->required(),
                Select::make('end_month')
                    ->label('Sampai Bulan')
                    ->options([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'])
                    ->default(12)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->required(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Keterangan')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state === DiscountType::Percent ? 'Persen' : 'Nominal'),
                TextColumn::make('value')
                    ->label('Nilai')
                    ->formatStateUsing(fn ($record): string => $record->type === DiscountType::Percent
                        ? $record->value.'%'
                        : 'Rp '.number_format($record->value, 0, ',', '.')),
                TextColumn::make('period')
                    ->label('Periode')
                    ->state(fn ($record): string => $record->start_month.'/'.$record->end_month),
                TextColumn::make('is_active')
                    ->label('Aktif')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'Ya' : 'Tidak')
                    ->color(fn ($state): array => $state ? ['success'] : ['danger']),
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
            ->defaultSort('student_id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDiscounts::route('/'),
        ];
    }
}
