<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Asignar slugs a registros existentes por nombre para preservar IDs
        Institution::where('name', 'Colegio de Ingenieros del Perú')->update(['slug' => 'cip']);
        Institution::where('name', 'Einsso Consultores')->update(['slug' => 'einsso']);
        Institution::where('name', 'Megapack')->update(['slug' => 'megapack']);

        // Slugs permitidos
        $allowedSlugs = ['cip', 'einsso', 'megapack'];

        // 2. Eliminar cualquier registro que no tenga uno de los slugs permitidos (incluyendo nulos no migrados)
        Institution::whereNotIn('slug', $allowedSlugs)
            ->orWhereNull('slug')
            ->delete();

        // 3. Asegurar que existan (crear si no existían ni por nombre)
        Institution::updateOrCreate(
            ['slug' => 'cip'],
            ['name' => 'Colegio de Ingenieros del Perú']
        );

        Institution::updateOrCreate(
            ['slug' => 'einsso'],
            ['name' => 'Einsso Consultores']
        );

        Institution::updateOrCreate(
            ['slug' => 'megapack'],
            ['name' => 'Megapack']
        );
    }
}
