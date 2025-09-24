<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';

    protected $fillable = [
        'user_id',
        'cliente_id',
        'caja_id',
        'subtotal_venta',
        'igv',
        'descuento_total',
        'total_venta',
        'fecha_venta',
        'hora_venta',
        'estado_venta',
        'metodo_pago',
        'cod_operacion',
    ];

    protected $casts = [
        'subtotal_venta' => 'decimal:2',
        'igv' => 'decimal:2',
        'descuento_total' => 'decimal:2',
        'total_venta' => 'decimal:2',
        'fecha_venta' => 'date',
        'hora_venta' => 'datetime:H:i',
    ];

    /**
     * Relación con usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con cliente
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Relación con caja
     */
    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    /**
     * Relación con detalle de ventas
     */
    public function detalleVentas(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    /**
     * Relación con comprobantes
     */
    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class, 'venta_id');
    }
}