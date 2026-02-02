<?php

namespace App\Filament\Admin\Resources\SystemSettingResource\Pages;

use App\Filament\Admin\Resources\SystemSettingResource;
use App\Models\SystemSetting;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class ManageSystemSettings extends EditRecord
{
    protected static string $resource = SystemSettingResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Aseguramos que siempre estemos editando el primer registro (o creándolo si no existe)
        $setting = SystemSetting::firstOrCreate([]);
        return $setting->toArray();
    }

    public function mount(int | string $record = null): void
    {
        // Siempre usar el primer registro (o crear uno nuevo)
        $this->record = SystemSetting::firstOrCreate([]);
        
        $this->fillForm();
        
        $this->previousUrl = url()->previous();
    }

    protected function getHeaderActions(): array
    {
        return [
            // No necesitamos acciones de header porque siempre estamos editando
        ];
    }
}
