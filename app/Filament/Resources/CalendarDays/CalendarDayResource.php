<?php

namespace App\Filament\Resources\CalendarDays;

use App\Enums\CalendarDayType;
use App\Filament\Resources\CalendarDays\Pages\ManageCalendarDays;
use App\Models\CalendarDay;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class CalendarDayResource extends Resource
{
    protected static ?string $model = CalendarDay::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Hari Libur';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kalender Hari Efektif';
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
            'viewAny', 'view' => 'operations.calendar.viewAny',
            'create' => 'operations.calendar.create',
            'update' => 'operations.calendar.update',
            'delete' => 'operations.calendar.delete',
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
                DatePicker::make('date')
                    ->label('Tanggal')
                    ->required(),
                Select::make('academic_term_id')
                    ->label('Semester')
                    ->relationship('academicTerm', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('type')
                    ->label('Jenis Hari')
                    ->options(CalendarDayType::class)
                    ->required(),
                TextInput::make('description')
                    ->label('Keterangan')
                    ->maxLength(255),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('academicTerm.name')
                    ->label('Semester')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge(),
                TextColumn::make('description')
                    ->label('Keterangan'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis Hari')
                    ->options(CalendarDayType::class),
                SelectFilter::make('academic_term_id')
                    ->label('Semester')
                    ->relationship('academicTerm', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCalendarDays::route('/'),
        ];
    }
}
