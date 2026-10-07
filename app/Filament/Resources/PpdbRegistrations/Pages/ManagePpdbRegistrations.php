<?php

namespace App\Filament\Resources\PpdbRegistrations\Pages;

use App\Exceptions\SchoolException;
use App\Filament\Resources\PpdbRegistrations\PpdbRegistrationResource;
use App\Models\PpdbRegistration;
use App\Services\School\PpdbService;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManagePpdbRegistrations extends ManageRecords
{
    protected static string $resource = PpdbRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Pendaftaran Baru')
                ->using(function (array $data): PpdbRegistration {
                    try {
                        return app(PpdbService::class)->createRegistration(
                            user: auth()->user(),
                            data: $data,
                        );
                    } catch (SchoolException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Gagal menyimpan pendaftaran')
                            ->body($exception->getMessage())
                            ->send();
                        $this->halt();
                    }
                }),
        ];
    }
}
