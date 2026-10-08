<?php

namespace App\Filament\Resources\StudentFees;

use App\Filament\Resources\FeeTypes\FeeTypeResource;
use App\Filament\Resources\StudentFees\Pages\ManageStudentFees;
use App\Models\AcademicYear;
use App\Models\StudentFee;
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

class StudentFeeResource extends Resource
{
    protected static ?string $model = StudentFee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 6;

    public static function getModelLabel(): string
    {
        return 'Biaya Siswa';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Penetapan Biaya Siswa';
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
                    ->required(),
                TextInput::make('amount')
                    ->label('Nominal per Bulan / Sekali (Rp)')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->prefix('Rp'),
                Select::make('months')
                    ->label('Jumlah Bulan')
                    ->options(array_combine(range(1, 12), range(1, 12)))
                    ->default(12)
                    ->required(),
                Select::make('first_month')
                    ->label('Mulai Bulan')
                    ->options([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'])
                    ->default(7)
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
                TextColumn::make('academicYear.name')
                    ->label('Tahun Ajaran')
                    ->badge(),
                TextColumn::make('feeType.name')
                    ->label('Jenis Biaya'),
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('months')
                    ->label('Bulan')
                    ->formatStateUsing(fn ($record): string => $record->first_month.'/'.$record->months.' bln'),
                TextColumn::make('is_active')
                    ->label('Aktif')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'Ya' : 'Tidak')
                    ->color(fn ($state): string => $state ? 'success' : 'danger'),
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
            ->defaultSort('student_id')
            ->poll('60s');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStudentFees::route('/'),
        ];
    }
}
