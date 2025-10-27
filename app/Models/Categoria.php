<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    protected $fillable = [
        'NombreCategoria',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    protected $attributes = [
        'estado' => true,
    ];

    /**
     * Relación con productos
     */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }

    /**
     * Scope para categorías activas
     */
    public function scopeActivas($query)
    {
        return $query->where('estado', true);
    }

    /**
     * Scope para categorías inactivas
     */
    public function scopeInactivas($query)
    {
        return $query->where('estado', false);
    }

    /**
     * Obtiene el conteo de productos asociados
     */
    public function getProductosCountAttribute(): int
    {
        return $this->productos()->count();
    }
}