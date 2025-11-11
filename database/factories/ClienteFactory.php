<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        $tipoDoc = fake()->randomElement(['DNI', 'RUC']);
        
        if ($tipoDoc === 'DNI') {
            $numDoc = fake()->numerify('########');
            $tipoCliente = 'natural';
        } else {
            // Para RUC, determinamos el tipo por el prefijo
            $prefijo = fake()->randomElement(['10', '20']);
            $numDoc = $prefijo . fake()->numerify('#########');
            $tipoCliente = $prefijo === '10' ? 'natural_con_negocio' : 'juridica';
        }

        return [
            'tipo_doc' => $tipoDoc,
            'tipo_cliente' => $tipoCliente,
            'num_doc' => $numDoc,
            'nombre_razon' => fake()->name(),
            'fecha_registro' => fake()->date('Y-m-d', 'now'),
            'estado' => fake()->randomElement(['activo', 'inactivo']),
            'telefono' => fake()->phoneNumber(),
            'direccion' => fake()->address(),
        ];
    }

    public function conDni(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_doc' => 'DNI',
            'tipo_cliente' => 'natural',
            'num_doc' => fake()->numerify('########'),
        ]);
    }

    public function conRucNatural(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_doc' => 'RUC',
            'tipo_cliente' => 'natural_con_negocio',
            'num_doc' => '10' . fake()->numerify('#########'),
        ]);
    }

    public function conRucJuridico(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_doc' => 'RUC',
            'tipo_cliente' => 'juridica',
            'num_doc' => '20' . fake()->numerify('#########'),
            'nombre_razon' => fake()->company(),
        ]);
    }

    public function activo(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'activo',
        ]);
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'inactivo',
        ]);
    }
}