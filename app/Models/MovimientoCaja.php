<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoCaja extends Model
{
    use HasFactory;

    protected $table = 'movimientos_caja';

    protected $fillable = [
        'caja_id',
        'tipo',
        'monto',
        'descripcion',
        'fecha_movimiento',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_movimiento' => 'date',
    ];

    /**
     * Relación con caja
     */
    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }
}