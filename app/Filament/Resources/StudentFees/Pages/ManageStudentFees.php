<?php

namespace App\Filament\Resources\StudentFees\Pages;

use App\Filament\Resources\StudentFees\StudentFeeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStudentFees extends ManageRecords
{
    protected static string $resource = StudentFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
