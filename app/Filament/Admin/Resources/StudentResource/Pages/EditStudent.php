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
        $dni = $this->data['dni_ce'] ?? null;
        if ($dni !== null) {
            $profile = $this->record->profile ?: $this->record->profile()->create();
            $profile->dni_ce = $dni;
            $profile->save();
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
