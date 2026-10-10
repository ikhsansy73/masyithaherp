<?php

namespace App\Filament\Resources\Leaves\Pages;

use App\Exceptions\SchoolException;
use App\Filament\Resources\Leaves\LeaveResource;
use App\Models\Employee;
use App\Models\Leave;
use App\Services\Payroll\LeaveService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Validation\ValidationException;

class ManageLeaves extends ManageRecords
{
    protected static string $resource = LeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Ajukan Izin/Cuti')
                ->using(function (array $data): Leave {
                    try {
                        return app(LeaveService::class)->create(
                            employee: Employee::query()->findOrFail($data['employee_id']),
                            data: [
                                'type' => $data['type'],
                                'start_date' => $data['start_date'],
                                'end_date' => $data['end_date'],
                                'days' => (int) ($data['days'] ?? 1),
                                'reason' => $data['reason'],
                            ],
                        );
                    } catch (SchoolException $exception) {
                        throw ValidationException::withMessages([
                            'start_date' => $exception->getMessage(),
                        ]);
                    }
                }),
        ];
    }
}
