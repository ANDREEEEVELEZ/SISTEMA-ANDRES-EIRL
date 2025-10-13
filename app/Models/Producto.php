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

    /**
     * Verifica si el producto está agotado
     */
    public function estaAgotado(): bool
    {
        return $this->stock_total <= 0;
    }

    /**
     * Verifica si el producto tiene stock bajo
     */
    public function tieneStockBajo(): bool
    {
        return $this->stock_total > 0 && $this->stock_total <= $this->stock_minimo;
    }

    /**
     * Obtiene el estado del stock
     */
    public function getEstadoStockAttribute(): string
    {
        if ($this->estaAgotado()) {
            return 'agotado';
        }
        
        if ($this->tieneStockBajo()) {
            return 'bajo';
        }
        
        return 'normal';
    }

    /**
     * Scope para productos con stock bajo
     */
    public function scopeStockBajo($query)
    {
        return $query->whereRaw('stock_total > 0 AND stock_total <= stock_minimo');
    }

    /**
     * Scope para productos agotados
     */
    public function scopeAgotados($query)
    {
        return $query->where('stock_total', '<=', 0);
    }

    /**
     * Scope para productos con alerta de stock
     */
    public function scopeConAlertaStock($query)
    {
        return $query->whereRaw('stock_total <= stock_minimo');
    }
}