<?php

namespace App\Filament\Admin\Resources\AllyResource\Pages;

use App\Filament\Admin\Resources\AllyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAlly extends CreateRecord
{
    protected static string $resource = AllyResource::class;

    protected static ?string $title = 'Nuevo Aliado';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
