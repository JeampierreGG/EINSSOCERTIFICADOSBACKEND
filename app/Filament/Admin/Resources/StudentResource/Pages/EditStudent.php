<?php

namespace App\Filament\Admin\Resources\StudentResource\Pages;

use App\Filament\Admin\Resources\StudentResource;
use App\Models\UserProfile;
use Filament\Resources\Pages\EditRecord;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected function afterSave(): void
    {
        $phoneCode = $this->data['phone_code'] ?? '+51';
        $phoneNumber = $this->data['phone_number'] ?? '';
        $fullPhone = $phoneNumber ? "({$phoneCode}) {$phoneNumber}" : null;

        $profileData = [
            'dni_ce' => $this->data['dni_ce'] ?? null,
            'nombres' => $this->data['nombres'] ?? null,
            'apellidos' => $this->data['apellidos'] ?? null,
            'phone' => $fullPhone,
            'country' => $this->data['country'] ?? null,
        ];

        // 1. Guardar/Actualizar Perfil
        $profile = $this->record->profile ?: $this->record->profile()->create();
        $profile->fill($profileData);
        $profile->save();

        // 2. Sincronizar el campo 'name' del usuario con (Nombres + Apellidos)
        $newName = trim(($profileData['nombres'] ?? '') . ' ' . ($profileData['apellidos'] ?? ''));
        if (!empty($newName) && $newName !== $this->record->name) {
            $this->record->name = $newName;
            $this->record->save();
        }
    }

    public function getHeading(): string
    {
        return 'Editar Estudiante';
    }

    protected function getRedirectUrl(): string
    {
        return StudentResource::getUrl('index');
    }
}
