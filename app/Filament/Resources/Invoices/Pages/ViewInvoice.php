<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    public function getHeading(): string
    {
        return 'Tagihan '.$this->getRecord()->number;
    }

    public function getTitle(): string
    {
        return 'Tagihan';
    }
}
