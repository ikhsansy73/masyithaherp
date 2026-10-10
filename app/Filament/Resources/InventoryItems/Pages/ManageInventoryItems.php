<?php

namespace App\Filament\Resources\InventoryItems\Pages;

use App\Exceptions\AccountingException;
use App\Filament\Resources\InventoryItems\InventoryItemResource;
use App\Models\Account;
use App\Models\CashAccount;
use App\Models\InventoryItem;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ManageInventoryItems extends ManageRecords
{
    protected static string $resource = InventoryItemResource::class;

    public function getHeaderActions(): array
    {
        return [
            self::stokMasukAction(),
            self::stokKeluarAction(),
        ];
    }

    private static function itemOptions(): array
    {
        return InventoryItem::query()->where('is_active', true)->orderBy('code')->get()
            ->mapWithKeys(fn (InventoryItem $item): array => [
                $item->getKey() => "[{$item->code}] {$item->name} (stok {$item->current_stock} {$item->unit->value})",
            ])->all();
    }

    private static function stokMasukAction(): Action
    {
        return Action::make('stokMasuk')
            ->label('Stok Masuk')
            ->icon('heroicon-m-arrow-down-tray')
            ->color('success')
            ->visible(fn (): bool => auth()->user()?->can('assets.asset.update') ?? false)
            ->modalWidth(Width::TwoExtraLarge)
            ->schema([
                Select::make('inventory_item_id')
                    ->label('Item')
                    ->options(fn (): array => self::itemOptions())
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('movement_date')
                    ->label('Tanggal')
                    ->default(today())
                    ->required(),
                TextInput::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->minValue(0.01)
                    ->required(),
                TextInput::make('unit_cost')
                    ->label('Harga per Unit (Rp)')
                    ->numeric()
                    ->minValue(1)
                    ->prefix('Rp')
                    ->required(),
                Select::make('cash_account_id')
                    ->label('Kas/Bank')
                    ->options(fn (): array => CashAccount::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->required(),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->maxLength(200),
            ])
            ->action(function (array $data): void {
                try {
                    app(InventoryService::class)->receive(
                        item: InventoryItem::query()->findOrFail((int) $data['inventory_item_id']),
                        movementDate: Carbon::parse($data['movement_date']),
                        quantity: (float) $data['quantity'],
                        unitCost: (int) $data['unit_cost'],
                        cashAccountId: (int) $data['cash_account_id'],
                        userId: (int) auth()->id(),
                        notes: $data['notes'] ?? null,
                    );
                } catch (AccountingException $exception) {
                    throw ValidationException::withMessages([
                        'quantity' => $exception->getMessage(),
                    ]);
                }

                Notification::make()
                    ->success()
                    ->title('Stok masuk tercatat.')
                    ->body('Jurnal pembelian (Dr 1-1400 / Cr kas) telah dibukukan.')
                    ->send();
            });
    }

    private static function stokKeluarAction(): Action
    {
        return Action::make('stokKeluar')
            ->label('Stok Keluar')
            ->icon('heroicon-m-arrow-up-tray')
            ->color('warning')
            ->visible(fn (): bool => auth()->user()?->can('assets.asset.update') ?? false)
            ->modalWidth(Width::TwoExtraLarge)
            ->schema([
                Select::make('inventory_item_id')
                    ->label('Item')
                    ->options(fn (): array => self::itemOptions())
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('movement_date')
                    ->label('Tanggal')
                    ->default(today())
                    ->required(),
                TextInput::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->minValue(0.01)
                    ->required(),
                Select::make('expense_account_id')
                    ->label('Beban')
                    ->options(fn (): array => Account::query()
                        ->whereIn('code', ['5-1400', '5-1600'])
                        ->orderBy('code')
                        ->get()
                        ->mapWithKeys(fn ($account): array => [
                            $account->getKey() => "[{$account->code}] {$account->name}",
                        ])->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('requester_id')
                    ->label('Pemohon')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->nullable(),
                TextInput::make('purpose')
                    ->label('Peruntukan')
                    ->maxLength(200),
            ])
            ->action(function (array $data): void {
                try {
                    app(InventoryService::class)->issue(
                        item: InventoryItem::query()->findOrFail((int) $data['inventory_item_id']),
                        movementDate: Carbon::parse($data['movement_date']),
                        quantity: (float) $data['quantity'],
                        expenseAccountId: (int) $data['expense_account_id'],
                        userId: (int) auth()->id(),
                        purpose: $data['purpose'] ?? null,
                        requesterId: filled($data['requester_id'] ?? null) ? (int) $data['requester_id'] : null,
                    );
                } catch (AccountingException $exception) {
                    throw ValidationException::withMessages([
                        'quantity' => $exception->getMessage(),
                    ]);
                }

                Notification::make()
                    ->success()
                    ->title('Stok keluar tercatat.')
                    ->body('Beban dibukukan dengan harga rata-rata tertimbang.')
                    ->send();
            });
    }
}
