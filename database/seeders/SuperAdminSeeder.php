<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Crear el rol super_admin si no existe
        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web'
        ]);

        // Crear usuario super admin (cambia estos datos)
        $user = User::updateOrCreate(
            ['email' => 'andreevelez2020@gmail.com'], // ⚠️

            [
                'name' => 'Super Administrador',
                'password' => Hash::make('73688748'), // ⚠️

                'is_active' => true,
            ]
        );

        // Asignar rol si no lo tiene
        if (!$user->hasRole('super_admin')) {
            $user->assignRole($role);
        }

        $this->command->info('✅
 Super Admin creado: ' . $user->email);
    }
}
