<?php

namespace App\Filament\Admin\Resources\PaymentMethodResource\Pages;

use App\Filament\Admin\Resources\PaymentMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentMethod extends CreateRecord
{
    protected static string $resource = PaymentMethodResource::class;

    protected static ?string $title = 'Crear Método de Pago';
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
