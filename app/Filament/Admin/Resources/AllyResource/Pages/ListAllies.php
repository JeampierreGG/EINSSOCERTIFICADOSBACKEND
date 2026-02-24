<?php

namespace App\Filament\Admin\Resources\AllyResource\Pages;

use App\Filament\Admin\Resources\AllyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAllies extends ListRecords
{
    protected static string $resource = AllyResource::class;

    protected static ?string $title = 'Aliados';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Aliado')
                ->icon('heroicon-o-plus'),
        ];
    }
}
