<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        $tipo_doc = $this->faker->randomElement(['DNI', 'RUC']);

        // Generar el número de documento según el tipo
        $num_doc = $tipo_doc === 'DNI'
            ? $this->faker->numerify('########')      // 8 dígitos
            : $this->faker->numerify('###########');  // 11 dígitos

        // Determinar tipo_cliente automáticamente
        $tipo_cliente = $tipo_doc === 'RUC' ? 'juridica' : 'natural';

        return [
            'estado' => 'activo',
            'tipo_doc' => $tipo_doc,
            'num_doc' => $num_doc,
            'nombre_razon' => $this->faker->company,
            'telefono' => $this->faker->numerify('9########'),
            'direccion' => $this->faker->address,
            'fecha_registro' => now(),
            'tipo_cliente' => $tipo_cliente,
        ];
    }

    /**
     * Estado con tipo_doc = DNI
     */
    public function conDNI()
    {
        return $this->state(fn() => [
            'tipo_doc' => 'DNI',
            'num_doc' => $this->faker->numerify('########'),
            'tipo_cliente' => 'natural',
        ]);
    }

    /**
     * Estado con tipo_doc = RUC
     */
    public function conRUC()
    {
        return $this->state(fn() => [
            'tipo_doc' => 'RUC',
            'num_doc' => $this->faker->numerify('###########'),
            'tipo_cliente' => 'juridica', // ← corregido según ENUM
        ]);
    }
}
