<?php

namespace App\Filament\Resources\Leaves;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Exceptions\SchoolException;
use App\Filament\Resources\Leaves\Pages\ManageLeaves;
use App\Models\Leave;
use App\Services\Payroll\LeaveService;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'SDM & Penggajian';

    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return 'Izin / Cuti';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Izin & Cuti';
    }

    /**
     * Leaves (doc 09): hr.leave — operator_tu manages,
     * kepala_sekolah/bendahara view; approval is hr.leave.approve.
     */
    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        $ability = $action instanceof \UnitEnum ? $action->name : $action;

        $permission = match ($ability) {
            'viewAny', 'view' => 'hr.leave.view',
            'create', 'update', 'replicate', 'reorder' => 'hr.leave.update',
            'delete', 'deleteAny', 'forceDelete', 'forceDeleteAny', 'restore', 'restoreAny' => 'hr.leave.delete',
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
                    ->disabled(fn (?Model $record): bool => $record !== null)
                    ->dehydrated(fn (?Model $record): bool => $record === null)
                    ->required(),
                Select::make('type')
                    ->label('Jenis')
                    ->options(collect(LeaveType::cases())
                        ->mapWithKeys(fn (LeaveType $type): array => [
                            $type->value => $type->label(),
                        ])->all())
                    ->required(),
                DatePicker::make('start_date')
                    ->label('Tanggal Mulai')
                    ->required(),
                DatePicker::make('end_date')
                    ->label('Tanggal Selesai')
                    ->afterOrEqual('start_date')
                    ->required(),
                TextInput::make('days')
                    ->label('Jumlah Hari')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->default(1)
                    ->required(),
                Textarea::make('reason')
                    ->label('Alasan')
                    ->required()
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function leaveStatusColor(LeaveStatus $state): array
    {
        return match ($state->color()) {
            'warning' => Color::Amber,
            'success' => Color::Emerald,
            'danger' => Color::Rose,
            default => Color::Gray,
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (LeaveType $state): string => $state->label())
                    ->color(fn (LeaveType $state): array => match ($state) {
                        LeaveType::Izin => Color::Blue,
                        LeaveType::Sakit => Color::Indigo,
                        LeaveType::Cuti => Color::Gray,
                        LeaveType::Dinas => Color::Teal,
                    }),
                TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date('d M Y'),
                TextColumn::make('days')
                    ->label('Hari'),
                TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(40),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (LeaveStatus $state): string => $state->label())
                    ->color(fn (LeaveStatus $state): array => static::leaveStatusColor($state)),
                TextColumn::make('approvedBy.name')
                    ->label('Diproses Oleh')
                    ->placeholder('-'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\Action::make('setujui')
                    ->label('Setujui')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record): bool => $record->status === LeaveStatus::Menunggu
                        && auth()->user()?->can('hr.leave.approve'))
                    ->action(function (Model $record): void {
                        try {
                            app(LeaveService::class)->approve($record, auth()->user());

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Pengajuan disetujui — absensi pegawai diperbarui')
                                ->send();
                        } catch (SchoolException $exception) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title($exception->getMessage())
                                ->send();
                        }
                    }),
                \Filament\Actions\Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record): bool => $record->status === LeaveStatus::Menunggu
                        && auth()->user()?->can('hr.leave.approve'))
                    ->action(function (Model $record): void {
                        try {
                            app(LeaveService::class)->reject($record, auth()->user());

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Pengajuan ditolak')
                                ->send();
                        } catch (SchoolException $exception) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title($exception->getMessage())
                                ->send();
                        }
                    }),
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Model $record): bool => $record->status === LeaveStatus::Menunggu)
                    ->using(function (Model $record, array $data): Model {
                        try {
                            return app(LeaveService::class)->update($record, $data);
                        } catch (SchoolException $exception) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'start_date' => $exception->getMessage(),
                            ]);
                        }
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', direction: 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLeaves::route('/'),
        ];
    }
}
