<?php

namespace Database\Factories;

use App\Models\Caja;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CajaFactory extends Factory
{
    protected $model = Caja::class;

    public function definition(): array
    {
        $fechaApertura = fake()->dateTimeBetween('-30 days', 'now');
        $estado = fake()->randomElement(['abierta', 'cerrada']);
        
        return [
            'user_id' => User::factory(),
            'fecha_apertura' => $fechaApertura,
            'fecha_cierre' => $estado === 'cerrada' ? fake()->dateTimeBetween($fechaApertura, 'now') : null,
            'saldo_inicial' => fake()->randomFloat(2, 100, 1000),
            'saldo_final' => $estado === 'cerrada' ? fake()->randomFloat(2, 100, 2000) : null,
            'estado' => $estado,
            'observacion' => fake()->optional()->sentence(),
        ];
    }

    public function abierta(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'abierta',
            'fecha_cierre' => null,
            'saldo_final' => null,
        ]);
    }

    public function cerrada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'cerrada',
            'fecha_cierre' => fake()->dateTimeBetween($attributes['fecha_apertura'], 'now'),
            'saldo_final' => fake()->randomFloat(2, 100, 2000),
        ]);
    }
}