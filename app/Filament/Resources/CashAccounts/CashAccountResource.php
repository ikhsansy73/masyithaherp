<?php

namespace App\Filament\Resources\CashAccounts;

use App\Enums\CashAccountType;
use App\Filament\Resources\CashAccounts\Pages\ManageCashAccounts;
use App\Models\CashAccount;
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

class CashAccountResource extends Resource
{
    protected static ?string $model = CashAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return 'Kas/Bank';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kas & Bank';
    }

    /**
     * Kas & Bank is the cash register master for billing transactions
     * (doc 09): bendahara manages, kepala_sekolah/operator_tu view.
     */
    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        $ability = $action instanceof \UnitEnum ? $action->name : $action;

        $permission = match ($ability) {
            'viewAny', 'view' => 'billing.transaction.view',
            'create', 'update', 'replicate', 'reorder' => 'billing.transaction.update',
            'delete', 'deleteAny', 'forceDelete', 'forceDeleteAny', 'restore', 'restoreAny' => 'billing.transaction.delete',
            default => null,
        };

        if ($permission === null) {
            return parent::getAuthorizationResponse($action, $record);
        }

        if ($record !== null && in_array($ability, ['delete', 'update'], true) && $record->payments()->exists()) {
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
                TextInput::make('name')
                    ->label('Nama Register')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->label('Jenis')
                    ->options(collect(CashAccountType::cases())
                        ->mapWithKeys(fn (CashAccountType $type): array => [
                            $type->value => $type->label(),
                        ])->all())
                    ->required(),
                Select::make('account_id')
                    ->label('Akun GL')
                    ->relationship('account', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->code} — {$record->name}")
                    ->searchable()
                    ->preload()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('Register kas memetakan ke akun 1-1100/1-1150/1-1200.'),
                TextInput::make('bank_name')
                    ->label('Nama Bank')
                    ->maxLength(255)
                    ->visible(fn (callable $get): bool => $get('type') === CashAccountType::Bank->value),
                TextInput::make('account_number')
                    ->label('Nomor Rekening')
                    ->maxLength(255)
                    ->visible(fn (callable $get): bool => $get('type') === CashAccountType::Bank->value),
                Toggle::make('is_default_kas')
                    ->label('Default Kas')
                    ->default(false),
                Toggle::make('is_default_bank')
                    ->label('Default Bank')
                    ->default(false),
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
                TextColumn::make('name')
                    ->label('Register')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (CashAccountType $state): string => $state->label())
                    ->color(fn (CashAccountType $state): array => match ($state) {
                        CashAccountType::Kas => Color::Green,
                        CashAccountType::Bank => Color::Blue,
                    }),
                TextColumn::make('account.code')
                    ->label('Akun GL')
                    ->placeholder('—'),
                TextColumn::make('account_number')
                    ->label('Rekening')
                    ->placeholder('—'),
                IconColumn::make('is_default_kas')
                    ->label('Def. Kas')
                    ->boolean(),
                IconColumn::make('is_default_bank')
                    ->label('Def. Bank')
                    ->boolean(),
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
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCashAccounts::route('/'),
        ];
    }
}
