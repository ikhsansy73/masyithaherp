<?php

namespace App\Filament\Resources\AssetMaintenances\Pages;

use App\Enums\MaintenanceType;
use App\Exceptions\AccountingException;
use App\Filament\Resources\AssetMaintenances\AssetMaintenanceResource;
use App\Models\Asset;
use App\Models\CashAccount;
use App\Services\Assets\AssetMaintenanceService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ManageAssetMaintenances extends ManageRecords
{
    protected static string $resource = AssetMaintenanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('catatPerawatan')
                ->label('Catat Perawatan')
                ->icon('heroicon-m-wrench-screwdriver')
                ->color('primary')
                ->visible(fn (): bool => auth()->user()?->can('assets.asset.update') ?? false)
                ->modalWidth(Width::TwoExtraLarge)
                ->schema([
                    Select::make('asset_id')
                        ->label('Aset')
                        ->options(fn (): array => Asset::query()
                            ->where('status', \App\Enums\AssetStatus::Aktif)
                            ->orderBy('code')
                            ->get()
                            ->mapWithKeys(fn (Asset $asset): array => [
                                $asset->getKey() => "[{$asset->code}] {$asset->name}",
                            ])->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    DatePicker::make('maintenance_date')
                        ->label('Tanggal')
                        ->default(today())
                        ->required(),
                    Select::make('type')
                        ->label('Jenis')
                        ->options(collect(MaintenanceType::cases())->mapWithKeys(
                            fn (MaintenanceType $type): array => [$type->value => $type->label()],
                        )->all())
                        ->default(MaintenanceType::Perawatan->value)
                        ->required(),
                    TextInput::make('description')
                        ->label('Uraian')
                        ->required()
                        ->maxLength(200),
                    TextInput::make('cost')
                        ->label('Biaya (Rp, 0 = catatan saja)')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('Rp')
                        ->default(0)
                        ->live(onBlur: true)
                        ->required(),
                    Select::make('cash_account_id')
                        ->label('Kas/Bank')
                        ->options(fn (): array => CashAccount::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->visible(fn (Get $get): bool => (int) ($get('cost') ?? 0) > 0)
                        ->required(fn (Get $get): bool => (int) ($get('cost') ?? 0) > 0),
                    TextInput::make('vendor')
                        ->label('Vendor')
                        ->maxLength(100),
                ])
                ->action(function (array $data): void {
                    try {
                        app(AssetMaintenanceService::class)->record(
                            asset: Asset::query()->findOrFail((int) $data['asset_id']),
                            maintenanceDate: Carbon::parse($data['maintenance_date']),
                            type: MaintenanceType::from($data['type']),
                            description: $data['description'],
                            cost: (int) $data['cost'],
                            cashAccountId: filled($data['cash_account_id'] ?? null) ? (int) $data['cash_account_id'] : null,
                            vendor: $data['vendor'] ?? null,
                            userId: (int) auth()->id(),
                        );
                    } catch (AccountingException $exception) {
                        throw ValidationException::withMessages([
                            'cost' => $exception->getMessage(),
                        ]);
                    }

                    Notification::make()
                        ->success()
                        ->title('Perawatan tercatat.')
                        ->body($data['cost'] > 0 ? 'Beban dibukukan ke 5-1800.' : 'Dicatat tanpa jurnal.')
                        ->send();
                }),
        ];
    }
}
