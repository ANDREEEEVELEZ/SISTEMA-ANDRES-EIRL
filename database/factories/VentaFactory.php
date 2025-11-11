<?php

namespace Database\Factories;

use App\Models\Venta;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Caja;
use Illuminate\Database\Eloquent\Factories\Factory;

class VentaFactory extends Factory
{
    protected $model = Venta::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 500);
        $igv = round($subtotal * 0.18, 2);
        $descuento = fake()->randomFloat(2, 0, $subtotal * 0.1);
        $total = $subtotal + $igv - $descuento;

        return [
            'user_id' => User::factory(),
            'cliente_id' => Cliente::factory(),
            'caja_id' => Caja::factory(),
            'subtotal_venta' => $subtotal,
            'igv' => $igv,
            'descuento_total' => $descuento,
            'total_venta' => $total,
            'fecha_venta' => fake()->date('Y-m-d', 'now'),
            'hora_venta' => fake()->time('H:i'),
            'estado_venta' => fake()->randomElement(['emitida', 'anulada', 'rechazada']),
            'metodo_pago' => fake()->randomElement(['efectivo', 'tarjeta', 'yape', 'plin', 'transferencia']),
            'cod_operacion' => fake()->optional(0.3)->numerify('OP-########'),
            'nombre_cliente_temporal' => fake()->optional(0.2)->name(),
        ];
    }

    public function emitida(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_venta' => 'emitida',
        ]);
    }

    public function anulada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_venta' => 'anulada',
        ]);
    }

    public function rechazada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_venta' => 'rechazada',
        ]);
    }

    public function enEfectivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'metodo_pago' => 'efectivo',
            'cod_operacion' => null,
        ]);
    }

    public function conTarjeta(): static
    {
        return $this->state(fn (array $attributes) => [
            'metodo_pago' => 'tarjeta',
            'cod_operacion' => 'TJ-' . fake()->numerify('########'),
        ]);
    }

    public function conYape(): static
    {
        return $this->state(fn (array $attributes) => [
            'metodo_pago' => 'yape',
            'cod_operacion' => 'YP-' . fake()->numerify('########'),
        ]);
    }

    public function sinDescuento(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal_venta'];
            $igv = round($subtotal * 0.18, 2);
            return [
                'descuento_total' => 0.00,
                'total_venta' => $subtotal + $igv,
            ];
        });
    }
}