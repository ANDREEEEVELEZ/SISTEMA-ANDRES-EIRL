<?php

namespace Database\Factories;

use App\Models\Empleado;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmpleadoFactory extends Factory
{
    protected $model = Empleado::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'dni' => fake()->unique()->numerify('########'),
            'telefono' => fake()->phoneNumber(),
            'direccion' => fake()->address(),
            'fecha_nacimiento' => fake()->date('Y-m-d', '2000-01-01'),
            'correo_empleado' => fake()->unique()->safeEmail(),
            'distrito' => fake()->city(),
            'fecha_incorporacion' => fake()->date('Y-m-d', 'now'),
            'estado_empleado' => fake()->randomElement(['activo', 'inactivo']),
            'foto_facial_path' => null,
            'face_descriptors' => null,
        ];
    }

    public function activo(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_empleado' => 'activo',
        ]);
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_empleado' => 'inactivo',
        ]);
    }
}