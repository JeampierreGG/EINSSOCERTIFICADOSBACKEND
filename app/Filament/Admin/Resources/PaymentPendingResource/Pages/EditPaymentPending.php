<?php

namespace App\Filament\Admin\Resources\PaymentPendingResource\Pages;

use App\Filament\Admin\Resources\PaymentPendingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentPending extends EditRecord
{
    protected static string $resource = PaymentPendingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
