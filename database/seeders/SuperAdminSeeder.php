<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar o crear el rol super_admin
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);

        // Opción 1: Asignar a un usuario por email específico
        $email = 'andreevelez2020@gmail.com'; // CAMBIAR ESTO POR EL CORREO DESEADO A SUPERADMIN
        
        $user = User::where('email', $email)->first();
        
        if ($user) {
            if (!$user->hasRole('super_admin')) {
                $user->assignRole('super_admin');
                $this->command->info("✓ Rol 'super_admin' asignado a: {$user->name} ({$user->email})");
            } else {
                $this->command->info("El usuario '{$user->name}' ya tiene el rol super_admin");
            }
        } else {
            $this->command->warn("⚠ No se encontró el usuario con email: {$email}");
            
            // Opción 2: Asignar al primer usuario si no se encuentra el email
            $primerUsuario = User::first();
            if ($primerUsuario && !$primerUsuario->hasRole('super_admin')) {
                $primerUsuario->assignRole('super_admin');
                $this->command->info("✓ Rol 'super_admin' asignado al primer usuario: {$primerUsuario->name} ({$primerUsuario->email})");
            }
        }
    }
}
