<?php

namespace App\Filament\Resources\JournalEntries;

use App\Enums\JournalSource;
use App\Enums\JournalStatus;
use App\Exceptions\AccountingException;
use App\Filament\Resources\JournalEntries\Pages\ManageJournalEntries;
use App\Models\Account;
use App\Models\Fund;
use App\Models\JournalEntry;
use App\Services\Accounting\JournalPostingService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Jurnal Umum';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Jurnal Umum';
    }

    /**
     * Filament v5 resolves resource-action authorization through the Gate
     * (policies), not the can*() overrides — so the spatie permission
     * matrix is mapped onto Filament abilities here. Posted entries are
     * immutable (doc 03 §3.3): every non-view ability is denied.
     */
    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        $ability = $action instanceof \UnitEnum ? $action->name : $action;

        $permission = match ($ability) {
            'viewAny' => 'accounting.journal.viewAny',
            'view' => 'accounting.journal.view',
            'create' => 'accounting.journal.create',
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
                DatePicker::make('entry_date')
                    ->label('Tanggal')
                    ->default(today())
                    ->required(),
                TextInput::make('description')
                    ->label('Keterangan')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Repeater::make('lines')
                    ->label('Baris Jurnal')
                    ->columns(5)
                    ->defaultItems(2)
                    ->schema([
                        Select::make('account_id')
                            ->label('Akun')
                            ->options(self::accountOptions())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('fund_id')
                            ->label('Dana')
                            ->options(Fund::query()->orderBy('code')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload(),
                        TextInput::make('debit')
                            ->label('Debit')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0),
                        TextInput::make('credit')
                            ->label('Kredit')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0),
                        TextInput::make('memo')
                            ->label('Memo')
                            ->maxLength(255),
                    ]),
            ])
            ->columns(1);
    }

    /**
     * Postable accounts (active, non-header) formatted "kode — nama".
     *
     * @return array<int, string>
     */
    private static function accountOptions(): array
    {
        return Account::query()
            ->where('is_active', true)
            ->where('is_header', false)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Account $account): array => [
                $account->getKey() => $account->code.' — '.$account->name,
            ])
            ->all();
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('number')
                    ->label('Nomor')
                    ->weight(FontWeight::SemiBold),
                TextEntry::make('entry_date')
                    ->label('Tanggal')
                    ->date(),
                TextEntry::make('description')
                    ->label('Keterangan')
                    ->columnSpanFull(),
                TextEntry::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn (JournalSource $state): string => ucfirst($state->value)),
                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (JournalStatus $state): string => $state->label())
                    ->color(fn (JournalStatus $state): array => $state === JournalStatus::Posted
                        ? Color::Green
                        : Color::Red),
                RepeatableEntry::make('lines')
                    ->label('Baris Jurnal')
                    ->columnSpanFull()
                    ->columns(5)
                    ->schema([
                        TextEntry::make('account.code')
                            ->label('Kode'),
                        TextEntry::make('account.name')
                            ->label('Akun'),
                        TextEntry::make('fund.name')
                            ->label('Dana')
                            ->placeholder('—'),
                        TextEntry::make('debit')
                            ->label('Debit')
                            ->money('IDR')
                            ->color(fn ($state): array => ((int) $state) > 0 ? Color::Green : Color::Gray),
                        TextEntry::make('credit')
                            ->label('Kredit')
                            ->money('IDR')
                            ->color(fn ($state): array => ((int) $state) > 0 ? Color::Green : Color::Gray),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold),
                TextColumn::make('entry_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn (JournalSource $state): string => ucfirst($state->value)),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (JournalStatus $state): string => $state->label())
                    ->color(fn (JournalStatus $state): array => $state === JournalStatus::Posted
                        ? Color::Green
                        : Color::Red),
                TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->placeholder('—'),
            ])
            ->recordActions([
                ViewAction::make(),
                self::voidAction(),
            ])
            ->defaultSort('entry_date', 'desc');
    }

    /**
     * Batalkan: posts a mirrored reversal through the posting service.
     */
    private static function voidAction(): Action
    {
        return Action::make('void')
            ->label('Batalkan')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('Membatalkan jurnal akan membuat jurnal balikan (mirror) otomatis.')
            ->schema([
                Textarea::make('reason')
                    ->label('Alasan Pembatalan')
                    ->required()
                    ->maxLength(255),
            ])
            ->visible(fn (JournalEntry $record): bool => $record->status === JournalStatus::Posted)
            ->action(function (JournalEntry $record, array $data): void {
                try {
                    app(JournalPostingService::class)->void($record, $data['reason']);

                    Notification::make()
                        ->success()
                        ->title('Jurnal dibatalkan.')
                        ->body('Jurnal balikan telah diposting otomatis.')
                        ->send();
                } catch (AccountingException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Jurnal tidak dapat dibatalkan.')
                        ->body($exception->getMessage())
                        ->send();
                }
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageJournalEntries::route('/'),
        ];
    }
}
