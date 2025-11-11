<?php

namespace Database\Factories;

use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'categoria_id' => Categoria::factory(),
            'nombre_producto' => fake()->words(3, true),
            'stock_total' => fake()->numberBetween(0, 100),
            'descripcion' => fake()->sentence(),
            'unidad_medida' => fake()->randomElement(['unidad', 'kilogramo', 'gramo', 'litro', 'metro', 'caja', 'docena']),
            'estado' => fake()->randomElement(['activo', 'inactivo']),
            'stock_minimo' => fake()->numberBetween(1, 10),
        ];
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

    public function agotado(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_total' => 0,
        ]);
    }

    public function stockBajo(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_total' => 3,
            'stock_minimo' => 5,
        ]);
    }

    public function stockNormal(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_total' => 50,
            'stock_minimo' => 10,
        ]);
    }

    public function conCategoria($categoriaId): static
    {
        return $this->state(fn (array $attributes) => [
            'categoria_id' => $categoriaId,
        ]);
    }
}