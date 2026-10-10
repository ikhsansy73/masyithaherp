<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Enums\AssetCondition;
use App\Enums\FundingSource;
use App\Exceptions\AccountingException;
use App\Filament\Resources\Assets\AssetResource;
use App\Models\AssetCategory;
use App\Models\CashAccount;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\Location;
use App\Services\Assets\AcquisitionData;
use App\Services\Assets\AssetService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ManageAssets extends ManageRecords
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('catatAset')
                ->label('Catat Aset')
                ->icon('heroicon-m-plus')
                ->color('primary')
                ->visible(fn (): bool => auth()->user()?->can('assets.asset.create') ?? false)
                ->modalWidth(Width::ThreeExtraLarge)
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Aset')
                        ->required()
                        ->maxLength(150),
                    Select::make('category_id')
                        ->label('Kelompok')
                        ->options(fn (): array => AssetCategory::query()
                            ->orderBy('code')
                            ->get()
                            ->mapWithKeys(fn (AssetCategory $category): array => [
                                $category->getKey() => "[{$category->code}] {$category->name}",
                            ])->all())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required(),
                    DatePicker::make('acquisition_date')
                        ->label('Tanggal Perolehan')
                        ->default(today())
                        ->required(),
                    TextInput::make('cost')
                        ->label('Biaya Perolehan (Rp)')
                        ->numeric()
                        ->minValue(1)
                        ->prefix('Rp')
                        ->required(),
                    Select::make('fund_id')
                        ->label('Sumber Dana (Fund)')
                        ->options(fn (): array => Fund::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(),
                    Select::make('funding_source')
                        ->label('Sumber Dana')
                        ->options(collect(FundingSource::cases())->mapWithKeys(
                            fn (FundingSource $source): array => [$source->value => $source->label()],
                        )->all())
                        ->required(),
                    Select::make('condition')
                        ->label('Kondisi')
                        ->options(collect(AssetCondition::cases())->mapWithKeys(
                            fn (AssetCondition $state): array => [$state->value => $state->label()],
                        )->all())
                        ->default(AssetCondition::Baik->value)
                        ->required(),
                    Select::make('location_id')
                        ->label('Lokasi')
                        ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('custodian_id')
                        ->label('Penanggung Jawab')
                        ->options(fn (): array => Employee::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload(),
                    TextInput::make('brand_model')
                        ->label('Merk/Tipe')
                        ->maxLength(100),
                    TextInput::make('serial_no')
                        ->label('No. Seri')
                        ->maxLength(100),
                    TextInput::make('useful_life_months')
                        ->label('Masa Manfaat (bulan)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(600)
                        ->placeholder(fn (Get $get): ?string => AssetCategory::find($get('category_id'))?->useful_life_months !== null
                            ? (string) AssetCategory::find($get('category_id'))?->useful_life_months
                            : null)
                        ->helperText('Kosong = pakai default kelompok.')
                        ->visible(fn (Get $get): bool => (bool) (AssetCategory::find($get('category_id'))?->is_depreciable ?? false)),
                    TextInput::make('salvage_value')
                        ->label('Nilai Sisa (Rp)')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('Rp')
                        ->default(0)
                        ->visible(fn (Get $get): bool => (bool) (AssetCategory::find($get('category_id'))?->is_depreciable ?? false)),
                    Radio::make('payment_mode')
                        ->label('Cara Bayar')
                        ->options([
                            'kas_bank' => 'Kas/Bank (lunas)',
                            'utang' => 'Utang Vendor (kredit)',
                        ])
                        ->default('kas_bank')
                        ->live()
                        ->required(),
                    Select::make('cash_account_id')
                        ->label('Kas/Bank')
                        ->options(fn (): array => CashAccount::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->default(fn (): ?int => CashAccount::query()->where('is_default_kas', true)->value('id'))
                        ->searchable()
                        ->live()
                        ->visible(fn (Get $get): bool => $get('payment_mode') === 'kas_bank')
                        ->required(fn (Get $get): bool => $get('payment_mode') === 'kas_bank'),
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    try {
                        $asset = app(AssetService::class)->acquire(new AcquisitionData(
                            categoryId: (int) $data['category_id'],
                            name: $data['name'],
                            acquisitionDate: Carbon::parse($data['acquisition_date']),
                            cost: (int) $data['cost'],
                            fundId: (int) $data['fund_id'],
                            fundingSource: FundingSource::from($data['funding_source']),
                            condition: AssetCondition::from($data['condition']),
                            locationId: (int) $data['location_id'],
                            custodianId: filled($data['custodian_id'] ?? null) ? (int) $data['custodian_id'] : null,
                            brandModel: $data['brand_model'] ?? null,
                            serialNo: $data['serial_no'] ?? null,
                            usefulLifeMonths: filled($data['useful_life_months'] ?? null) ? (int) $data['useful_life_months'] : null,
                            salvageValue: (int) ($data['salvage_value'] ?? 0),
                            paymentMode: $data['payment_mode'],
                            cashAccountId: filled($data['cash_account_id'] ?? null) ? (int) $data['cash_account_id'] : null,
                            notes: $data['notes'] ?? null,
                            userId: (int) auth()->id(),
                        ));
                    } catch (AccountingException $exception) {
                        throw ValidationException::withMessages([
                            'cost' => $exception->getMessage(),
                        ]);
                    }

                    Notification::make()
                        ->success()
                        ->title("Aset {$asset->code} tercatat")
                        ->body('Jurnal akuisisi (JE #12) telah dibukukan.')
                        ->send();
                }),
        ];
    }
}
