<?php

namespace App\Filament\Admin\Resources\InstitutionResource\Pages;

use App\Filament\Admin\Resources\InstitutionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInstitution extends EditRecord
{
    protected static string $resource = InstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return InstitutionResource::getUrl('index');
    }
}
