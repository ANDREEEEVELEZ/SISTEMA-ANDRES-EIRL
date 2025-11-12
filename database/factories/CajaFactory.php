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
        return [
            'user_id' => User::factory(),
            'fecha_apertura' => now(),
            'saldo_inicial' => $this->faker->randomFloat(2, 100, 1000),
            'estado' => 'abierta',
            'observacion' => $this->faker->optional()->sentence(),
        ];
    }

    public function cerrada(): static
    {
        return $this->state(function (array $attributes) {
            $saldoFinal = $this->faker->randomFloat(2, 500, 2000);

            return [
                'fecha_cierre' => now(),
                'saldo_final' => $saldoFinal,
                'estado' => 'cerrada',
            ];
        });
    }

    public function abierta(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha_cierre' => null,
            'saldo_final' => null,
            'estado' => 'abierta',
        ]);
    }
}
