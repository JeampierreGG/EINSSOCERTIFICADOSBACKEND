<?php

namespace App\Filament\Admin\Resources\PaymentCompletedResource\Pages;

use App\Filament\Admin\Resources\PaymentCompletedResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentCompleted extends EditRecord
{
    protected static string $resource = PaymentCompletedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
