<?php

namespace App\Filament\Resources\StudentMovements;

use App\Enums\MovementType;
use App\Filament\Resources\StudentMovements\Pages\ManageStudentMovements;
use App\Models\StudentMovement;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only audit trail of every placement change (doc 06 §3). Rows are
 * written only by StudentMovementService / PpdbService — no create or
 * edit from the UI.
 */
class StudentMovementResource extends Resource
{
    protected static ?string $model = StudentMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|\UnitEnum|null $navigationGroup = 'Siswa & PPDB';

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return 'Peristiwa Siswa';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Riwayat Pergerakan Siswa';
    }

    /**
     * Filament v5 resolves resource-action authorization through the Gate
     * (policies), not the can*() overrides — so the spatie permission
     * matrix is mapped onto Filament abilities here. Audit rows are
     * immutable: only view abilities are granted.
     */
    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        $ability = $action instanceof \UnitEnum ? $action->name : $action;

        $permission = match ($ability) {
            'viewAny' => 'students.student.viewAny',
            'view' => 'students.student.view',
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

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('student.full_name')->label('Siswa'),
            TextEntry::make('academicYear.name')->label('Tahun Ajaran'),
            TextEntry::make('type')->label('Jenis')->badge(),
            TextEntry::make('fromClassroom.name')->label('Rombel Asal')->placeholder('-'),
            TextEntry::make('toClassroom.name')->label('Rombel Tujuan')->placeholder('-'),
            TextEntry::make('movement_date')->label('Tanggal')->date(),
            TextEntry::make('notes')->label('Catatan')->placeholder('-'),
            TextEntry::make('registeredBy.name')->label('Dicatat Oleh'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.nis')
                    ->label('NIS')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('student.full_name')
                    ->label('Siswa')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (MovementType $state): string => match ($state) {
                        MovementType::Kenaikan, MovementType::MutasiMasuk => 'success',
                        MovementType::TinggalKelas => 'warning',
                        MovementType::Lulus => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('fromClassroom.name')
                    ->label('Dari')
                    ->placeholder('-'),
                TextColumn::make('toClassroom.name')
                    ->label('Ke')
                    ->placeholder('-'),
                TextColumn::make('movement_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('registeredBy.name')
                    ->label('Dicatat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options(MovementType::class),
                SelectFilter::make('academic_year_id')
                    ->label('Tahun Ajaran')
                    ->relationship('academicYear', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStudentMovements::route('/'),
        ];
    }
}
