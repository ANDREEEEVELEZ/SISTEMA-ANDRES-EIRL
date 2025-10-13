<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventario extends Model
{
    use HasFactory;

    protected $table = 'movimientos_inventario';

    // Constantes para tipos de movimiento
    public const TIPO_ENTRADA = 'entrada';
    public const TIPO_SALIDA = 'salida';
    public const TIPO_AJUSTE = 'ajuste';

    // Constantes para métodos de ajuste
    public const METODO_ABSOLUTO = 'absoluto';
    public const METODO_RELATIVO = 'relativo';

    // Constantes para motivos de ajuste
    public const MOTIVO_CONTEO_FISICO = 'conteo_fisico';
    public const MOTIVO_VENCIDO = 'vencido';
    public const MOTIVO_DANADO = 'danado';
    public const MOTIVO_ROBO = 'robo';
    public const MOTIVO_OTRO = 'otro';

    protected $fillable = [
        'producto_id',
        'user_id',
        'tipo',
        'metodo_ajuste',
        'motivo_ajuste',
        'cantidad_movimiento',
        'motivo_movimiento',
        'fecha_movimiento',
    ];

    protected $casts = [
        'cantidad_movimiento' => 'integer',
        'fecha_movimiento' => 'date',
    ];

    /**
     * Relación con producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Relación con usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}