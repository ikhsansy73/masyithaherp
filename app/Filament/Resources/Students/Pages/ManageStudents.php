<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use Filament\Resources\Pages\ManageRecords;

class ManageStudents extends ManageRecords
{
    protected static string $resource = StudentResource::class;
}
