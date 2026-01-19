<?php

namespace App\Filament\Admin\Resources\PaymentCompletedResource\Pages;

use App\Filament\Admin\Resources\PaymentCompletedResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentCompleteds extends ListRecords
{
    protected static string $resource = PaymentCompletedResource::class;

    public function getTitle(): string 
    {
        return 'Pagos Revisados';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
