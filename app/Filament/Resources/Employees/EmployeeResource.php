<?php

namespace App\Filament\Resources\Employees;

use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Filament\Resources\Employees\Pages\ManageEmployees;
use App\Models\Employee;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|\UnitEnum|null $navigationGroup = 'SDM & Penggajian';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Pegawai';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Data Pegawai';
    }

    /**
     * Filament v5 resolves resource-action authorization through the Gate
     * (policies), not the can*() overrides — so the spatie permission
     * matrix is mapped onto Filament abilities here.
     */
    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        $ability = $action instanceof \UnitEnum ? $action->name : $action;

        $permission = match ($ability) {
            'viewAny', 'view' => 'hr.employee.viewAny',
            'create' => 'hr.employee.create',
            'update' => 'hr.employee.update',
            'delete' => 'hr.employee.delete',
            default => null,
        };

        if ($permission === null) {
            return Response::deny();
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
                Select::make('user_id')
                    ->label('Akun Login')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('employee_no')
                    ->label('No. Pegawai')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(20),
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('nik')
                    ->label('NIK')
                    ->numeric()
                    ->maxLength(16),
                Select::make('gender')
                    ->label('Jenis Kelamin')
                    ->options(Gender::class)
                    ->required(),
                TextInput::make('birth_place')
                    ->label('Tempat Lahir')
                    ->maxLength(255),
                DatePicker::make('birth_date')
                    ->label('Tanggal Lahir'),
                Textarea::make('address')
                    ->label('Alamat')
                    ->columnSpanFull(),
                TextInput::make('phone')
                    ->label('Telepon')
                    ->tel()
                    ->maxLength(20),
                TextInput::make('position')
                    ->label('Jabatan')
                    ->required()
                    ->maxLength(255),
                Select::make('employment_status')
                    ->label('Status Kepegawaian')
                    ->options(EmploymentStatus::class)
                    ->required(),
                Toggle::make('is_teaching')
                    ->label('Tenaga Pendidik')
                    ->default(false),
                DatePicker::make('join_date')
                    ->label('Tanggal Masuk'),
                DatePicker::make('end_date')
                    ->label('Tanggal Berhenti'),
                TextInput::make('marital_status')
                    ->label('Status Perkawinan')
                    ->maxLength(50),
                TextInput::make('bank_name')
                    ->label('Bank')
                    ->maxLength(100),
                TextInput::make('bank_account_no')
                    ->label('No. Rekening')
                    ->maxLength(50),
                TextInput::make('bpjs_kesehatan_no')
                    ->label('No. BPJS Kesehatan')
                    ->maxLength(50),
                TextInput::make('bpjs_ketenagakerjaan_no')
                    ->label('No. BPJS Ketenagakerjaan')
                    ->maxLength(50),
                TextInput::make('npwp_no')
                    ->label('No. NPWP')
                    ->maxLength(50),
                TextInput::make('base_salary')
                    ->label('Gaji Pokok')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_no')
                    ->label('No. Pegawai')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('position')
                    ->label('Jabatan')
                    ->searchable(),
                TextColumn::make('employment_status')
                    ->label('Status')
                    ->badge(),
                IconColumn::make('is_teaching')
                    ->label('Pengajar')
                    ->boolean(),
                TextColumn::make('phone')
                    ->label('Telepon'),
                TextColumn::make('base_salary')
                    ->label('Gaji Pokok')
                    ->money('IDR')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_teaching')
                    ->label('Tenaga Pendidik'),
                TernaryFilter::make('is_active')
                    ->label('Aktif'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SalaryComponentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEmployees::route('/'),
            'view' => Pages\ViewEmployee::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
