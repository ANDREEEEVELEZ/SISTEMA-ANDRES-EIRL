<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\EstadoArqueo;

class Arqueo extends Model
{
    use HasFactory;

    protected $table = 'arqueos';

    protected $fillable = [
        'caja_id',
        'user_id',
        'fecha_inicio',
        'fecha_fin',
        'saldo_inicial',
        'total_ventas',
        'total_ingresos',
        'total_egresos',
        'saldo_teorico',
        'efectivo_contado',
        'diferencia',
        'observacion',
        'estado',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'saldo_inicial' => 'decimal:2',
        'total_ventas' => 'decimal:2',
        'total_ingresos' => 'decimal:2',
        'total_egresos' => 'decimal:2',
        'saldo_teorico' => 'decimal:2',
    'efectivo_contado' => 'decimal:2',
    'diferencia' => 'decimal:2',
    'estado' => EstadoArqueo::class,
    ];

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener el número secuencial del arqueo (no el ID)
     * Cuenta cuántos arqueos existen con ID menor o igual a este
     */
    public function getNumeroSecuencialAttribute(): int
    {
        return self::where('id', '<=', $this->id)->count();
    }

    /**
     * Obtener el número de arqueo formateado
     */
    public function getNumeroFormateadoAttribute(): string
    {
        return 'Arqueo #' . $this->numero_secuencial;
    }
}
