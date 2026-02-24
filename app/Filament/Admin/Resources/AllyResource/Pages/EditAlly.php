<?php

namespace App\Filament\Admin\Resources\AllyResource\Pages;

use App\Filament\Admin\Resources\AllyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAlly extends EditRecord
{
    protected static string $resource = AllyResource::class;

    protected static ?string $title = 'Editar Aliado';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Eliminar Aliado'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
