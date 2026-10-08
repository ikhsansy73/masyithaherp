<?php

namespace App\Filament\Resources\PpdbRegistrations;

use App\Enums\Gender;
use App\Enums\PpdbStatus;
use App\Enums\Religion;
use App\Exceptions\SchoolException;
use App\Filament\Resources\PpdbRegistrations\Pages\ManagePpdbRegistrations;
use App\Models\Classroom;
use App\Models\PpdbRegistration;
use App\Services\School\PpdbService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class PpdbRegistrationResource extends Resource
{
    protected static ?string $model = PpdbRegistration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|\UnitEnum|null $navigationGroup = 'Siswa & PPDB';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Pendaftaran';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pendaftaran PPDB';
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
            'viewAny' => 'ppdb.viewAny',
            'view' => 'ppdb.view',
            'create' => 'ppdb.create',
            'update' => 'ppdb.update',
            'delete' => 'ppdb.delete',
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
        return $schema->components([
            Select::make('academic_year_id')
                ->label('Tahun Ajaran')
                ->relationship('academicYear', 'name')
                ->required(),
            TextInput::make('applicant_name')
                ->label('Nama Calon Siswa')
                ->required()
                ->maxLength(255),
            Select::make('gender')
                ->label('Jenis Kelamin')
                ->options(Gender::class)
                ->required(),
            DatePicker::make('birth_date')
                ->label('Tanggal Lahir')
                ->maxDate(today()),
            TextInput::make('birth_place')
                ->label('Tempat Lahir')
                ->maxLength(255),
            Select::make('religion')
                ->label('Agama')
                ->options(Religion::class),
            TextInput::make('nik')
                ->label('NIK')
                ->numeric()
                ->maxLength(16),
            TextInput::make('origin_tk')
                ->label('Asal TK/PAUD')
                ->maxLength(255),
            Textarea::make('address')
                ->label('Alamat')
                ->columnSpanFull(),
            TextInput::make('father_name')
                ->label('Nama Ayah')
                ->required()
                ->maxLength(255),
            TextInput::make('mother_name')
                ->label('Nama Ibu')
                ->required()
                ->maxLength(255),
            TextInput::make('parent_phone')
                ->label('Telepon Orang Tua')
                ->required()
                ->maxLength(20),
            Textarea::make('notes')
                ->label('Catatan')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('registration_no')->label('No. Pendaftaran'),
            TextEntry::make('academicYear.name')->label('Tahun Ajaran'),
            TextEntry::make('applicant_name')->label('Nama Calon Siswa'),
            TextEntry::make('gender')->label('L/P')->badge(),
            TextEntry::make('birth_place')->label('Tempat Lahir'),
            TextEntry::make('birth_date')->label('Tanggal Lahir')->date(),
            TextEntry::make('religion')->label('Agama')->badge(),
            TextEntry::make('nik')->label('NIK'),
            TextEntry::make('origin_tk')->label('Asal TK/PAUD'),
            TextEntry::make('address')->label('Alamat')->columnSpanFull(),
            TextEntry::make('father_name')->label('Nama Ayah'),
            TextEntry::make('mother_name')->label('Nama Ibu'),
            TextEntry::make('parent_phone')->label('Telepon Orang Tua'),
            TextEntry::make('status')->label('Status')->badge(),
            TextEntry::make('registered_at')->label('Didaftarkan')->dateTime(),
            TextEntry::make('notes')->label('Catatan')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('registration_no')
                    ->label('No. Pendaftaran')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('applicant_name')
                    ->label('Calon Siswa')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('gender')
                    ->label('L/P')
                    ->badge(),
                TextColumn::make('parent_phone')
                    ->label('Telepon'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (PpdbStatus $state): string => match ($state) {
                        PpdbStatus::Baru => 'gray',
                        PpdbStatus::Verifikasi => 'info',
                        PpdbStatus::Diterima => 'success',
                        PpdbStatus::Cadangan => 'warning',
                        PpdbStatus::Ditolak => 'danger',
                        PpdbStatus::Terdaftar => 'primary',
                    })
                    ->formatStateUsing(fn (PpdbStatus $state): string => $state->label()),
                TextColumn::make('registered_at')
                    ->label('Tanggal Daftar')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(PpdbStatus::class),
                SelectFilter::make('academic_year_id')
                    ->label('Tahun Ajaran')
                    ->relationship('academicYear', 'name'),
            ])
            ->recordActions([
                self::workflowActions(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * PPDB status workflow actions for one registration: Verifikasi /
     * Terima / Cadangan / Tolak / Daftarkan, each gated to the record's
     * current status.
     */
    private static function workflowActions(): ActionGroup
    {
        return ActionGroup::make([
            self::verifyAction(),
            self::acceptAction(),
            self::holdAction(),
            self::rejectAction(),
            self::daftarkanAction(),
        ]);
    }

    private static function verifyAction(): Action
    {
        return Action::make('verifikasi')
            ->label('Verifikasi')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->visible(fn (PpdbRegistration $record): bool => $record->status === PpdbStatus::Baru)
            ->requiresConfirmation()
            ->action(function (PpdbRegistration $record): void {
                try {
                    app(PpdbService::class)->verify($record, auth()->user());
                    Notification::make()
                        ->success()
                        ->title('Pendaftaran diverifikasi')
                        ->send();
                } catch (SchoolException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Gagal verifikasi')
                        ->body($exception->getMessage())
                        ->send();
                }
            });
    }

    private static function acceptAction(): Action
    {
        return Action::make('terima')
            ->label('Terima')
            ->icon(Heroicon::OutlinedHandThumbUp)
            ->visible(fn (PpdbRegistration $record): bool => $record->status === PpdbStatus::Verifikasi)
            ->requiresConfirmation()
            ->action(function (PpdbRegistration $record): void {
                try {
                    app(PpdbService::class)->accept($record, auth()->user());
                    Notification::make()
                        ->success()
                        ->title('Pendaftaran diterima')
                        ->send();
                    $record->refresh();
                } catch (SchoolException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Gagal menerima')
                        ->body($exception->getMessage())
                        ->send();
                }
            });
    }

    private static function holdAction(): Action
    {
        return Action::make('cadangkan')
            ->label('Cadangkan')
            ->icon(Heroicon::OutlinedClock)
            ->visible(fn (PpdbRegistration $record): bool => $record->status === PpdbStatus::Verifikasi)
            ->requiresConfirmation()
            ->action(function (PpdbRegistration $record): void {
                try {
                    app(PpdbService::class)->putOnHold($record, auth()->user());
                    Notification::make()
                        ->success()
                        ->title('Pendaftaran dicadangkan')
                        ->send();
                    $record->refresh();
                } catch (SchoolException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Gagal mencadangkan')
                        ->body($exception->getMessage())
                        ->send();
                }
            });
    }

    private static function rejectAction(): Action
    {
        return Action::make('tolak')
            ->label('Tolak')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (PpdbRegistration $record): bool => $record->status === PpdbStatus::Verifikasi)
            ->requiresConfirmation()
            ->action(function (PpdbRegistration $record): void {
                try {
                    app(PpdbService::class)->reject($record, auth()->user());
                    Notification::make()
                        ->success()
                        ->title('Pendaftaran ditolak')
                        ->send();
                    $record->refresh();
                } catch (SchoolException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Gagal menolak')
                        ->body($exception->getMessage())
                        ->send();
                }
            });
    }

    private static function daftarkanAction(): Action
    {
        return Action::make('daftarkan')
            ->label('Daftarkan')
            ->icon(Heroicon::OutlinedUserPlus)
            ->color('success')
            ->visible(fn (PpdbRegistration $record): bool => $record->status === PpdbStatus::Diterima)
            ->schema([
                Select::make('classroom_id')
                    ->label('Rombel Tujuan')
                    ->options(fn (PpdbRegistration $record): array => Classroom::query()
                        ->where('academic_year_id', $record->academic_year_id)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->required(),
                Toggle::make('is_transfer')
                    ->label('Pindahan (mutasi masuk)')
                    ->default(false),
                Toggle::make('create_portal_account')
                    ->label('Buat akun portal wali')
                    ->default(false),
                TextInput::make('guardian_email')
                    ->label('Email Wali')
                    ->email()
                    ->required(fn (Get $get): bool => (bool) $get('create_portal_account')),
                TextInput::make('wali_name')
                    ->label('Nama Wali (opsional)')
                    ->maxLength(255),
                TextInput::make('wali_phone')
                    ->label('Telepon Wali (opsional)')
                    ->maxLength(20),
            ])
            ->action(function (array $data, PpdbRegistration $record, Action $action): void {
                try {
                    $result = app(PpdbService::class)->daftarkan(
                        registration: $record,
                        actor: auth()->user(),
                        targetClassroom: Classroom::query()->findOrFail($data['classroom_id']),
                        isTransfer: (bool) $data['is_transfer'],
                        createPortalAccount: (bool) $data['create_portal_account'],
                        guardianEmail: $data['guardian_email'] ?: null,
                        waliName: $data['wali_name'] ?: null,
                        waliPhone: $data['wali_phone'] ?: null,
                    );

                    Notification::make()
                        ->success()
                        ->title('Siswa terdaftar')
                        ->body('NIS '.$result['student']->nis)
                        ->send();
                } catch (SchoolException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Gagal mendaftarkan siswa')
                        ->body($exception->getMessage())
                        ->send();
                    $action->halt();
                }
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePpdbRegistrations::route('/'),
        ];
    }
}
