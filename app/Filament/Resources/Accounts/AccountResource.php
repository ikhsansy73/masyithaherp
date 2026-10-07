<?php

namespace App\Filament\Resources\Accounts;

use App\Enums\AccountType;
use App\Enums\CashFlowCategory;
use App\Enums\NormalBalance;
use App\Filament\Resources\Accounts\Pages\ManageAccounts;
use App\Models\Account;
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
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Akun';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Daftar Akun (COA)';
    }

    /**
     * Filament v5 resolves resource-action authorization through the Gate
     * (policies), not the can*() overrides — so the spatie permission
     * matrix is mapped onto Filament abilities here, keeping one source
     * of truth (doc 09).
     */
    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        $ability = $action instanceof \UnitEnum ? $action->name : $action;

        $permission = match ($ability) {
            'viewAny' => 'accounting.coa.viewAny',
            'view' => 'accounting.coa.view',
            'create' => 'accounting.coa.create',
            'update', 'replicate', 'reorder' => 'accounting.coa.update',
            'delete', 'deleteAny', 'forceDelete', 'forceDeleteAny', 'restore', 'restoreAny' => 'accounting.coa.delete',
            default => null,
        };

        if ($permission === null) {
            return parent::getAuthorizationResponse($action, $record);
        }

        if ($record !== null && $ability === 'update' && $record->is_locked) {
            return Response::deny();
        }

        if ($record !== null && $ability === 'delete' && ($record->is_locked || $record->journalLines()->exists())) {
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
                TextInput::make('code')
                    ->label('Kode Akun')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->regex('/^5-\d{4}$/', 'Kode akun baru harus berformat 5-xxxx (kelompok beban).')
                    ->disabledOn('edit')
                    ->dehydrated(),
                TextInput::make('name')
                    ->label('Nama Akun')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Select::make('type')
                    ->label('Kelompok Akun')
                    ->options([AccountType::Beban->value => AccountType::Beban->label()])
                    ->default(AccountType::Beban->value)
                    ->disabledOn('edit')
                    ->dehydrated()
                    ->required(),
                Select::make('normal_balance')
                    ->label('Saldo Normal')
                    ->options([
                        NormalBalance::Debit->value => NormalBalance::Debit->label(),
                        NormalBalance::Kredit->value => NormalBalance::Kredit->label(),
                    ])
                    ->default(NormalBalance::Debit->value)
                    ->required(),
                Select::make('cash_flow_category')
                    ->label('Kategori Arus Kas')
                    ->options(collect(CashFlowCategory::cases())
                        ->mapWithKeys(fn (CashFlowCategory $category): array => [
                            $category->value => $category->label(),
                        ])
                        ->all())
                    ->default(CashFlowCategory::Operasi->value)
                    ->required(),
                Select::make('default_fund_id')
                    ->label('Dana Bawaan')
                    ->relationship('defaultFund', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
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
                    ->weight(FontWeight::SemiBold),
                TextColumn::make('name')
                    ->label('Nama Akun')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Kelompok')
                    ->badge()
                    ->formatStateUsing(fn (AccountType $state): string => $state->label())
                    ->color(fn (AccountType $state): array => match ($state) {
                        AccountType::Aset => Color::Blue,
                        AccountType::Kewajiban => Color::Amber,
                        AccountType::Ekuitas => Color::Purple,
                        AccountType::Pendapatan => Color::Green,
                        AccountType::Beban => Color::Rose,
                    }),
                TextColumn::make('normal_balance')
                    ->label('Saldo Normal')
                    ->badge()
                    ->formatStateUsing(fn (NormalBalance $state): string => $state->label()),
                TextColumn::make('defaultFund.name')
                    ->label('Dana Bawaan')
                    ->placeholder('—'),
                IconColumn::make('is_header')
                    ->label('Grup')
                    ->boolean(),
                IconColumn::make('is_locked')
                    ->label('Terkunci')
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
            ->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAccounts::route('/'),
        ];
    }
}
