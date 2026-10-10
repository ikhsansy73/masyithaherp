<?php

namespace App\Filament\Resources\EmployeeAttendances\Pages;

use App\Enums\EmployeeAttendanceStatus;
use App\Exceptions\SchoolException;
use App\Filament\Resources\EmployeeAttendances\EmployeeAttendanceResource;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Services\Payroll\EmployeeAttendanceService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ManageEmployeeAttendances extends ManageRecords
{
    protected static string $resource = EmployeeAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Catat Absensi')
                ->using(function (array $data): EmployeeAttendance {
                    try {
                        return app(EmployeeAttendanceService::class)->record(
                            employee: Employee::query()->findOrFail($data['employee_id']),
                            date: Carbon::parse($data['date']),
                            status: EmployeeAttendanceStatus::from($data['status']),
                            checkIn: $data['check_in'] ?? null,
                            checkOut: $data['check_out'] ?? null,
                            notes: $data['notes'] ?? null,
                        );
                    } catch (SchoolException $exception) {
                        throw ValidationException::withMessages([
                            'date' => $exception->getMessage(),
                        ]);
                    }
                }),
        ];
    }
}
