<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('SUPERADMIN_EMAIL');
        $password = env('SUPERADMIN_PASSWORD');
        $adminRoleId = optional(Role::where('name', 'Administrador')->first())->id;

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Superadmin EINSSO',
                'password' => Hash::make($password),
                'is_admin' => true,
                'role_id' => $adminRoleId,
            ]
        );
    }
}
