<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserProfile;

class PurgeUsers extends Command
{
    protected $signature = 'app:purge-users {--adminId=1 : ID del usuario admin a conservar}';

    protected $description = 'Elimina todos los usuarios y perfiles excepto el usuario admin indicado';

    public function handle(): int
    {
        $adminId = (int) $this->option('adminId');

        UserProfile::query()->where('user_id', '!=', $adminId)->delete();
        User::query()->where('id', '!=', $adminId)->delete();

        $this->info("Usuarios y perfiles purgados, preservando admin ID {$adminId}.");
        return self::SUCCESS;
    }
}

