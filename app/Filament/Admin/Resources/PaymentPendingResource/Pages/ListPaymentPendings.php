<?php

namespace App\Filament\Admin\Resources\PaymentPendingResource\Pages;

use App\Filament\Admin\Resources\PaymentPendingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentPendings extends ListRecords
{
    protected static string $resource = PaymentPendingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
