<?php

namespace App\Filament\Admin\Resources\InstitutionResource\Pages;

use App\Filament\Admin\Resources\InstitutionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInstitution extends CreateRecord
{
    protected static string $resource = InstitutionResource::class;

    protected function getRedirectUrl(): string
    {
        return InstitutionResource::getUrl('index');
    }
}
