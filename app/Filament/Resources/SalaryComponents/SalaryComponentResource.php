<?php

namespace App\Filament\Resources\SalaryComponents;

use App\Enums\SalaryCalculation;
use App\Enums\SalaryComponentType;
use App\Filament\Resources\SalaryComponents\Pages\ManageSalaryComponents;
use App\Models\SalaryComponent;
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
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class SalaryComponentResource extends Resource
{
    protected static ?string $model = SalaryComponent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static string|\UnitEnum|null $navigationGroup = 'SDM & Penggajian';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return 'Komponen Gaji';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Komponen Gaji';
    }

    /**
     * Salary component master (doc 09): payroll.component — bendahara
     * manages, kepala_sekolah view.
     */
    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        $ability = $action instanceof \UnitEnum ? $action->name : $action;

        $permission = match ($ability) {
            'viewAny', 'view' => 'payroll.component.view',
            'create', 'update', 'replicate', 'reorder' => 'payroll.component.update',
            'delete', 'deleteAny', 'forceDelete', 'forceDeleteAny', 'restore', 'restoreAny' => 'payroll.component.delete',
            default => null,
        };

        if ($permission === null) {
            return parent::getAuthorizationResponse($action, $record);
        }

        return auth()->user()?->can($permission)
            ? Response::allow()
            : Response::deny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Kode')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(20)
                    ->helperText('Contoh: GAJI_POKOK, BPJS_KES_PEG. Kode GAJI_POKOK wajib ada.'),
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->label('Jenis')
                    ->options(collect(SalaryComponentType::cases())
                        ->mapWithKeys(fn (SalaryComponentType $type): array => [
                            $type->value => $type->label(),
                        ])->all())
                    ->required(),
                Select::make('calculation')
                    ->label('Perhitungan')
                    ->options(collect(SalaryCalculation::cases())
                        ->mapWithKeys(fn (SalaryCalculation $calculation): array => [
                            $calculation->value => $calculation->label(),
                        ])->all())
                    ->required()
                    ->helperText('Persen Basis = % dari nominal GAJI_POKOK; Entri Manual = diinput per periode.'),
                TextInput::make('default_amount')
                    ->label('Nominal Default')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                TextInput::make('percent_rate')
                    ->label('Persentase')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(1)
                    ->step(0.0001)
                    ->helperText('0.01 = 1%. Hanya untuk perhitungan Persen Basis.'),
                Toggle::make('is_employer')
                    ->label('Ditanggung Sekolah')
                    ->helperText('BPJS bagian sekolah: beban sekolah, tercantum di slip tanpa mengurangi neto.'),
                Select::make('gl_account_id')
                    ->label('Akun Beban')
                    ->relationship('glAccount', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->code} - {$record->name}")
                    ->searchable()
                    ->preload()
                    ->helperText('Wajib untuk pendapatan (5-11xx); kosong untuk potongan.'),
                Select::make('liability_account_id')
                    ->label('Akun Utang')
                    ->relationship('liabilityAccount', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->code} - {$record->name}")
                    ->searchable()
                    ->preload()
                    ->helperText('Wajib untuk potongan dan BPJS bagian sekolah (2-11xx/2-12xx/2-13xx).'),
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
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (SalaryComponentType $state): string => $state->label())
                    ->color(fn (SalaryComponentType $state): array => match ($state) {
                        SalaryComponentType::Pendapatan => Color::Emerald,
                        SalaryComponentType::Potongan => Color::Rose,
                    }),
                TextColumn::make('calculation')
                    ->label('Perhitungan')
                    ->badge()
                    ->formatStateUsing(fn (SalaryCalculation $state): string => $state->label())
                    ->color(fn (SalaryCalculation $state): array => match ($state) {
                        SalaryCalculation::Fixed => Color::Blue,
                        SalaryCalculation::PercentBase => Color::Amber,
                        SalaryCalculation::ManualEntry => Color::Gray,
                    }),
                TextColumn::make('default_amount')
                    ->label('Nominal Default')
                    ->money('IDR'),
                TextColumn::make('percent_rate')
                    ->label('%')
                    ->formatStateUsing(fn ($state): string => $state === null ? '-' : number_format((float) $state * 100, 2, ',', '.').'%'),
                IconColumn::make('is_employer')
                    ->label('Sekolah')
                    ->boolean(),
                TextColumn::make('glAccount.code')
                    ->label('Akun Beban')
                    ->placeholder('-'),
                TextColumn::make('liabilityAccount.code')
                    ->label('Akun Utang')
                    ->placeholder('-'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
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
            ->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSalaryComponents::route('/'),
        ];
    }
}
