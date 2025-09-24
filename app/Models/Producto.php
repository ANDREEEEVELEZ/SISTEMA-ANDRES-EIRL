<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'nombre_producto',
        'stock_total',
        'descripcion',
        'unidad_medida',
        'estado',
        'stock_minimo',
    ];

    protected $casts = [
        'stock_total' => 'integer',
        'stock_minimo' => 'integer',
    ];

    /**
     * Relación con categoría
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * Relación con precios
     */
    public function preciosProductos(): HasMany
    {
        return $this->hasMany(PrecioProducto::class, 'producto_id');
    }

    /**
     * Relación con producción diaria
     */
    public function produccionesDiarias(): HasMany
    {
        return $this->hasMany(ProduccionDiaria::class, 'producto_id');
    }

    /**
     * Relación con detalle de ventas
     */
    public function detalleVentas(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'producto_id');
    }

    /**
     * Relación con movimientos de inventario
     */
    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'producto_id');
    }
}