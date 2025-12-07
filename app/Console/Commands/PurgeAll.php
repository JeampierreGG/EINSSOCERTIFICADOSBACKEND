<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeAll extends Command
{
    protected $signature = 'app:purge-all {--adminId=1 : ID del usuario admin a conservar}';

    protected $description = 'Elimina todos los registros excepto la tabla roles y el usuario admin indicado';

    public function handle(): int
    {
        $adminId = (int) $this->option('adminId');

        DB::transaction(function () use ($adminId) {
            // Tablas de tokens y jobs
            DB::table('failed_jobs')->delete();
            DB::table('personal_access_tokens')->delete();
            DB::table('password_reset_tokens')->delete();

            // Items y certificados primero
            DB::table('certificate_items')->delete();
            DB::table('certificates')->delete();

            // Instituciones
            DB::table('institutions')->delete();

            // Perfiles de usuarios (excepto admin)
            DB::table('user_profiles')->where('user_id', '!=', $adminId)->delete();

            // Usuarios (excepto admin)
            DB::table('users')->where('id', '!=', $adminId)->delete();

            // Importante: No tocar la tabla roles
        });

        $this->info("Purgado completado. Se conservó el usuario admin ID {$adminId} y la tabla roles.");
        return self::SUCCESS;
    }
}

