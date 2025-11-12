<?php

namespace Database\Factories;

use App\Models\SerieComprobante;
use Illuminate\Database\Eloquent\Factories\Factory;

class SerieComprobanteFactory extends Factory
{
    protected $model = SerieComprobante::class;

    public function definition(): array
    {
        $tipo = $this->faker->randomElement(['boleta', 'factura']);

        return [
            'tipo' => $tipo,
            'codigo_tipo_comprobante' => $tipo === 'boleta' ? '03' : '01',
            'aplica_a' => 'ninguno',
            'serie' => strtoupper($tipo === 'boleta' ? 'B001' : 'F001'),
            'ultimo_numero' => 0,
        ];
    }
}
