<?php

namespace App\Filament\Resources\EmployeeAttendances;

use App\Enums\EmployeeAttendanceStatus;
use App\Filament\Resources\EmployeeAttendances\Pages\ManageEmployeeAttendances;
use App\Models\EmployeeAttendance;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class EmployeeAttendanceResource extends Resource
{
    protected static ?string $model = EmployeeAttendance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'SDM & Penggajian';

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return 'Absensi Pegawai';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Absensi Pegawai';
    }

    /**
     * Attendance (doc 09): hr.attendance — operator_tu manages,
     * kepala_sekolah/bendahara view.
     */
    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        $ability = $action instanceof \UnitEnum ? $action->name : $action;

        $permission = match ($ability) {
            'viewAny', 'view' => 'hr.attendance.view',
            'create', 'update', 'replicate', 'reorder' => 'hr.attendance.update',
            'delete', 'deleteAny', 'forceDelete', 'forceDeleteAny', 'restore', 'restoreAny' => 'hr.attendance.delete',
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
                Select::make('employee_id')
                    ->label('Pegawai')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('date')
                    ->label('Tanggal')
                    ->maxDate(today())
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options(collect(EmployeeAttendanceStatus::cases())
                        ->mapWithKeys(fn (EmployeeAttendanceStatus $status): array => [
                            $status->value => $status->label(),
                        ])->all())
                    ->required(),
                TimePicker::make('check_in')
                    ->label('Jam Masuk')
                    ->seconds(false),
                TimePicker::make('check_out')
                    ->label('Jam Keluar')
                    ->seconds(false),
                TextInput::make('notes')
                    ->label('Catatan')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (EmployeeAttendanceStatus $state): string => $state->label())
                    ->color(fn (EmployeeAttendanceStatus $state): array => match ($state->color()) {
                        'success' => Color::Emerald,
                        'warning' => Color::Amber,
                        'info' => Color::Blue,
                        'danger' => Color::Rose,
                        default => Color::Gray,
                    }),
                TextColumn::make('check_in')
                    ->label('Masuk'),
                TextColumn::make('check_out')
                    ->label('Keluar'),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(40)
                    ->placeholder('-'),
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
            ->defaultSort('date', direction: 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEmployeeAttendances::route('/'),
        ];
    }
}
