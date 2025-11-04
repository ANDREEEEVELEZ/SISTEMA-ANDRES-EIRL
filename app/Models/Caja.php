<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caja extends Model
{
    use HasFactory;

    protected $table = 'cajas';

    protected $fillable = [
        'user_id',
        'fecha_apertura',
        'fecha_cierre',
        'saldo_inicial',
        'saldo_final',
        'estado',
        'observacion',
    ];

    protected $casts = [
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
        'saldo_inicial' => 'decimal:2',
        'saldo_final' => 'decimal:2',
    ];

    /**
     * Relación con usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con movimientos de caja
     */
    public function movimientosCaja(): HasMany
    {
        return $this->hasMany(MovimientoCaja::class, 'caja_id');
    }

    /**
     * Relación con ventas
     */
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'caja_id');
    }

    /**
     * Obtener el número secuencial de la caja (no el ID)
     * Cuenta cuántas cajas existen con ID menor o igual a esta
     */
    public function getNumeroSecuencialAttribute(): int
    {
        return self::where('id', '<=', $this->id)->count();
    }

    /**
     * Obtener el número de caja formateado
     */
    public function getNumeroFormateadoAttribute(): string
    {
        return 'Caja #' . $this->numero_secuencial;
    }
}
