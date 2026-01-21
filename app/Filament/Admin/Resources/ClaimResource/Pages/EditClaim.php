<?php

namespace App\Filament\Admin\Resources\ClaimResource\Pages;

use App\Filament\Admin\Resources\ClaimResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClaim extends EditRecord
{
    protected static string $resource = ClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
