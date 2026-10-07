<?php

namespace App\Filament\Resources\AcademicYears\Pages;

use App\Filament\Resources\AcademicYears\AcademicYearResource;
use App\Models\AcademicYear;
use App\Services\School\AcademicYearService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAcademicYears extends ManageRecords
{
    protected static string $resource = AcademicYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): AcademicYear {
                    return app(AcademicYearService::class)->create(
                        $data['name'],
                        (bool) ($data['is_default'] ?? false),
                    );
                }),
        ];
    }
}
