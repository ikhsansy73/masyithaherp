<?php

namespace App\Filament\Resources\AssetOpnames\Pages;

use App\Exceptions\AccountingException;
use App\Filament\Resources\AssetOpnames\AssetOpnameResource;
use App\Models\Location;
use App\Services\Assets\AssetOpnameService;
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

class ManageAssetOpnames extends ManageRecords
{
    protected static string $resource = AssetOpnameResource::class;

    public function getHeaderActions(): array
    {
        return [
            self::buatOpnameAction(),
        ];
    }

    private static function buatOpnameAction(): Action
    {
        return Action::make('buatOpname')
            ->label('Buat Opname')
            ->icon('heroicon-m-clipboard-document-check')
            ->visible(fn (): bool => auth()->user()?->can('assets.asset.update') ?? false)
            ->modalWidth(Width::TwoExtraLarge)
            ->schema([
                TextInput::make('name')
                    ->label('Nama Opname')
                    ->default(fn (): string => 'Opname '.today()->translatedFormat('F Y'))
                    ->required()
                    ->maxLength(100),
                DatePicker::make('opname_date')
                    ->label('Tanggal')
                    ->default(today())
                    ->required(),
                Select::make('location_id')
                    ->label('Lokasi (opsional)')
                    ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->helperText('Kosongkan untuk memuat semua aset aktif.'),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->maxLength(500),
            ])
            ->action(function (array $data): void {
                try {
                    $result = app(AssetOpnameService::class)->create(
                        name: $data['name'],
                        opnameDate: Carbon::parse($data['opname_date']),
                        userId: (int) auth()->id(),
                        locationId: filled($data['location_id'] ?? null) ? (int) $data['location_id'] : null,
                        notes: $data['notes'] ?? null,
                    );
                } catch (AccountingException $exception) {
                    throw ValidationException::withMessages([
                        'name' => $exception->getMessage(),
                    ]);
                }

                Notification::make()
                    ->success()
                    ->title("Opname dibuat — {$result['itemCount']} aset dimuat.")
                    ->body('Periksa tiap item di halaman opname, lalu klik Finalisasi.')
                    ->send();
            });
    }
}
