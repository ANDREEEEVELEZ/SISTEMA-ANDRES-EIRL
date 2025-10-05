<?php

namespace App\Filament\Resources\Empleados\Pages;

use App\Filament\Resources\Empleados\EmpleadoResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateEmpleado extends CreateRecord
{
    protected static string $resource = EmpleadoResource::class;

    /**
     * Mutate los datos antes de crear el registro
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Crear automáticamente un usuario para el empleado
        $user = User::create([
            'name' => $data['nombres'] . ' ' . $data['apellidos'],
            'email' => $data['correo_empleado'] ?? strtolower(Str::slug($data['nombres'] . '-' . $data['apellidos'])) . '@empresa.com',
            'password' => Hash::make($data['dni']), // Contraseña inicial es el DNI
        ]);

        // Asignar el rol de empleado (si usas Spatie Laravel Permission)
        // $user->assignRole('empleado');

        // Asignar el user_id al empleado
        $data['user_id'] = $user->id;

        return $data;
    }
}
