<?php

namespace App\Filament\Admin\Resources\CertificateResource\Pages;

use App\Filament\Admin\Resources\CertificateResource;
use App\Models\Certificate;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Str;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCertificate extends CreateRecord
{
    protected static string $resource = CertificateResource::class;

    protected function getRedirectUrl(): string
    {
        return CertificateResource::getUrl('index');
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Sanitizar longitud de título para evitar errores de DB
        if (!empty($data['title'])) {
            $data['title'] = \Illuminate\Support\Str::limit(trim((string) $data['title']), 255, '');
        }
        // Resolver usuario: vincular por nombre (si existe), si no crear con el nombre ingresado
        // Resolver usuario: crear o buscar
        if (empty($data['user_id'])) {
            $nombres = trim((string) ($data['nombres'] ?? ''));
            $apellidos = trim((string) ($data['apellidos'] ?? ''));
            $fullName = trim("$nombres $apellidos");

            // Validar que tengamos nombre para crear
            if ($fullName !== '') {
                $studentRoleId = 2; // Estudiante
                
                // Intentar buscar por nombre si no tenemos ID (seguridad extra)
                $user = User::where('is_admin', false)->where('name', $fullName)->first();

                if (!$user) {
                    $user = User::create([
                        'name' => $fullName,
                        'is_admin' => false,
                        'role_id' => $studentRoleId,
                        'email' => null, // Opcional o generar uno dummy
                        'password' => null,
                    ]);
                }
                $data['user_id'] = $user->id;
            }
        }

        // Crear/Actualizar perfil
        if (!empty($data['user_id'])) {
            $updates = [];
            if (!empty($data['nombres'])) $updates['nombres'] = $data['nombres'];
            if (!empty($data['apellidos'])) $updates['apellidos'] = $data['apellidos'];
            if (!empty($data['dni_ce'])) $updates['dni_ce'] = $data['dni_ce'];

            if (!empty($updates)) {
                UserProfile::updateOrCreate(
                    ['user_id' => $data['user_id']],
                    $updates
                );
            }
        }

        if (($data['type'] ?? null) === 'megapack') {
            $groupId = (string) Str::uuid();
            $items = $data['items'] ?? [];

            if (empty($items)) {
                // Si no hay ítems, creamos al menos un certificado vacío con grupo
                return Certificate::create([
                    'user_id' => $data['user_id'] ?? null,
                    'type' => 'megapack',
                    'megapack_group_id' => $groupId,
                ]);
            }

            // Usar el primer ítem para el registro principal que Filament espera
            $first = $items[0];
            $main = Certificate::create([
                'user_id' => $data['user_id'] ?? null,
                'type' => 'megapack',
                'megapack_group_id' => $groupId,
                'institution_id' => $first['institution_id'] ?? null,
                'title' => isset($first['title']) ? \Illuminate\Support\Str::limit(trim((string) $first['title']), 255, '') : null,
                'category' => $first['category'] ?? null,
                'hours' => $first['hours'] ?? null,
                'grade' => $first['grade'] ?? null,
                'issue_date' => $first['issue_date'] ?? null,
                'code' => $first['code'] ?? null,
                'file_path' => $first['file_path'] ?? null,
            ]);

            // Crear los certificados restantes del megapack
            foreach (array_slice($items, 1) as $item) {
                Certificate::create([
                    'user_id' => $data['user_id'] ?? null,
                    'type' => 'megapack',
                    'megapack_group_id' => $groupId,
                    'institution_id' => $item['institution_id'] ?? null,
                    'title' => isset($item['title']) ? \Illuminate\Support\Str::limit(trim((string) $item['title']), 255, '') : null,
                    'category' => $item['category'] ?? null,
                    'hours' => $item['hours'] ?? null,
                    'grade' => $item['grade'] ?? null,
                    'issue_date' => $item['issue_date'] ?? null,
                    'code' => $item['code'] ?? null,
                    'file_path' => $item['file_path'] ?? null,
                ]);
            }

            return $main;
        }

        // Creación manual para 'solo' asegurando DNI/CE y unicidad de código (validada en formulario)
        $cert = Certificate::create([
            'user_id' => $data['user_id'] ?? null,
            'type' => 'solo',
            'institution_id' => $data['institution_id'] ?? null,
            'title' => $data['title'] ?? null,
            'category' => $data['category'] ?? null,
            'hours' => $data['hours'] ?? null,
            'grade' => $data['grade'] ?? null,
            'issue_date' => $data['issue_date'] ?? null,
            'code' => $data['code'] ?? null,
            'file_path' => $data['file_path'] ?? null,
        ]);

        // Sincronizar perfil del usuario existente
        if (!empty($data['user_id']) && !empty($data['dni_ce'])) {
            UserProfile::updateOrCreate(
                ['user_id' => $data['user_id']],
                ['dni_ce' => $data['dni_ce']]
            );
        }

        return $cert;
    }
}
