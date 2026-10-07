<?php

namespace App\Filament\Resources\Students;

use App\Enums\Gender;
use App\Enums\Religion;
use App\Enums\StudentStatus;
use App\Filament\Resources\Students\Pages\ManageStudents;
use App\Filament\Resources\Students\Pages\ViewStudent;
use App\Filament\Resources\Students\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Resources\Students\RelationManagers\GuardiansRelationManager;
use App\Filament\Resources\Students\RelationManagers\StudentFeesRelationManager;
use App\Models\Student;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|\UnitEnum|null $navigationGroup = 'Siswa & PPDB';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Siswa';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Data Siswa';
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
            'viewAny' => 'students.student.viewAny',
            'view' => 'students.student.view',
            'create' => 'students.student.create',
            'update' => 'students.student.update',
            'delete' => 'students.student.delete',
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
            SpatieMediaLibraryFileUpload::make('photo')
                ->label('Foto')
                ->collection('photo')
                ->image()
                ->imageEditor()
                ->maxSize(2048)
                ->columnSpanFull(),
            TextInput::make('nis')
                ->label('NIS')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(20),
            TextInput::make('nisn')
                ->label('NISN')
                ->numeric()
                ->unique(ignoreRecord: true)
                ->minLength(10)
                ->maxLength(10),
            TextInput::make('nik')
                ->label('NIK')
                ->numeric()
                ->unique(ignoreRecord: true)
                ->maxLength(16),
            TextInput::make('full_name')
                ->label('Nama Lengkap')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Select::make('gender')
                ->label('Jenis Kelamin')
                ->options(Gender::class)
                ->required(),
            DatePicker::make('birth_date')
                ->label('Tanggal Lahir')
                ->required(),
            TextInput::make('birth_place')
                ->label('Tempat Lahir')
                ->maxLength(255),
            Select::make('religion')
                ->label('Agama')
                ->options(Religion::class)
                ->required(),
            Textarea::make('address')
                ->label('Alamat')
                ->columnSpanFull(),
            TextInput::make('kk_no')
                ->label('No. KK')
                ->numeric()
                ->maxLength(16),
            TextInput::make('akta_no')
                ->label('No. Akta')
                ->numeric()
                ->maxLength(100),
            TextInput::make('phone')
                ->label('Telepon')
                ->maxLength(20),
            Select::make('status')
                ->label('Status')
                ->options(StudentStatus::class)
                ->required()
                ->default(StudentStatus::Aktif),
            DatePicker::make('entry_date')
                ->label('Tanggal Masuk'),
            DatePicker::make('exit_date')
                ->label('Tanggal Keluar')
                ->visible(fn (string $operation): bool => $operation === 'edit'),
            TextInput::make('exit_reason')
                ->label('Alasan Keluar')
                ->visible(fn (string $operation): bool => $operation === 'edit'),
        ])->columns(2);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            SpatieMediaLibraryFileUpload::make('photo')
                ->label('Foto')
                ->collection('photo')
                ->image()
                ->columnSpanFull(),
            TextEntry::make('nis')->label('NIS'),
            TextEntry::make('nisn')->label('NISN'),
            TextEntry::make('nik')->label('NIK'),
            TextEntry::make('full_name')->label('Nama Lengkap'),
            TextEntry::make('gender')->label('Jenis Kelamin')->badge(),
            TextEntry::make('birth_place')->label('Tempat Lahir'),
            TextEntry::make('birth_date')->label('Tanggal Lahir')->date(),
            TextEntry::make('religion')->label('Agama')->badge(),
            TextEntry::make('phone')->label('Telepon'),
            TextEntry::make('address')->label('Alamat')->columnSpanFull(),
            TextEntry::make('status')->label('Status')->badge(),
            TextEntry::make('entry_date')->label('Tanggal Masuk')->date(),
            TextEntry::make('exit_date')->label('Tanggal Keluar')->date(),
            TextEntry::make('exit_reason')->label('Alasan Keluar'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('photo')
                    ->label('Foto')
                    ->collection('photo')
                    ->circular(),
                TextColumn::make('nis')
                    ->label('NIS')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('gender')
                    ->label('L/P')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (StudentStatus $state): string => match ($state) {
                        StudentStatus::Aktif => 'success',
                        StudentStatus::Lulus => 'info',
                        StudentStatus::MutasiKeluar => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('classrooms')
                    ->label('Rombel')
                    ->state(function (Student $record): string {
                        return $record->enrollments()
                            ->with('classroom')
                            ->where('status', 'aktif')
                            ->get()
                            ->map(fn ($enrollment): string => $enrollment->classroom?->name ?? '-')
                            ->implode(', ') ?: '-';
                    }),
                TextColumn::make('attendances_count')
                    ->label('Absensi')
                    ->counts('attendances'),
                TextColumn::make('entry_date')
                    ->label('Tanggal Masuk')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StudentStatus::class),
                SelectFilter::make('gender')
                    ->label('Jenis Kelamin')
                    ->options(Gender::class),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            GuardiansRelationManager::class,
            EnrollmentsRelationManager::class,
            StudentFeesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStudents::route('/'),
            'view' => ViewStudent::route('/{record}'),
        ];
    }
}
