<?php

namespace App\Filament\Admin\Resources\PaymentFinalizedResource\Pages;

use App\Filament\Admin\Resources\PaymentFinalizedResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePaymentFinalizeds extends ManageRecords
{
    protected static string $resource = PaymentFinalizedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
