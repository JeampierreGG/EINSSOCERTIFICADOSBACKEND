<?php

namespace App\Filament\Admin\Resources\TeacherResource\Pages;

use App\Filament\Admin\Resources\TeacherResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateTeacher extends CreateRecord
{
    protected static string $resource = TeacherResource::class;

    protected function getRedirectUrl(): string
    {
        return TeacherResource::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $userName = $data['user_name'] ?? null;

        if ($userName) {
            // Crear usuario automáticamente
            $user = User::create([
                'name' => $userName,
                'email' => 'docente_' . time() . '_' . Str::random(5) . '@system.local',
                'password' => Hash::make(Str::random(16)),
                // Puedes asignar un rol si es necesario, e.g., 'role_id' => 3
            ]);

            $data['user_id'] = $user->id;
        }
        
        unset($data['user_name']);

        return $data;
    }
}
