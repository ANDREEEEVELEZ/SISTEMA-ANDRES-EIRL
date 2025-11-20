<?php

namespace Database\Factories;

use App\Models\Asistencia;
use App\Models\Empleado;
use Illuminate\Database\Eloquent\Factories\Factory;

class AsistenciaFactory extends Factory
{
    protected $model = Asistencia::class;

    public function definition(): array
    {
        $fecha = fake()->dateTimeBetween('-30 days', 'now');
        $horaEntrada = fake()->time('H:i', '09:00');
        $horaSalida = fake()->time('H:i', '18:00');

        return [
            'empleado_id' => Empleado::factory(),
            'fecha' => $fecha->format('Y-m-d'),
            'hora_entrada' => $horaEntrada,
            'hora_salida' => $horaSalida,
            'estado' => fake()->randomElement(['presente', 'falta', 'permiso']),
            'observacion' => fake()->optional()->sentence(),
            'metodo_registro' => fake()->randomElement(['facial', 'manual_dni']),
            'razon_manual' => null,
        ];
    }

    public function presente(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'presente',
            'hora_entrada' => '08:00',
            'hora_salida' => '17:00',
        ]);
    }

    public function falta(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'falta',
            'hora_entrada' => null,
            'hora_salida' => null,
            'observacion' => 'No asistió',
        ]);
    }

    public function conPermiso(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'permiso',
            'observacion' => 'Permiso médico',
        ]);
    }

    public function registroFacial(): static
    {
        return $this->state(fn (array $attributes) => [
            'metodo_registro' => 'facial',
            'razon_manual' => null,
        ]);
    }

    public function registroManual(): static
    {
        return $this->state(fn (array $attributes) => [
            'metodo_registro' => 'manual_dni',
            'razon_manual' => 'Sistema biométrico no disponible',
        ]);
    }

    public function conEmpleado($empleadoId): static
    {
        return $this->state(fn (array $attributes) => [
            'empleado_id' => $empleadoId,
        ]);
    }
}