<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comprobante extends Model
{
    use HasFactory;

    protected $table = 'comprobantes';

    protected $fillable = [
        'venta_id',
        'serie_comprobante_id',
        'tipo',
        'codigo_tipo_nota',
        'serie',
        'correlativo',
        'fecha_emision',
        'sub_total',
        'igv',
        'total',
        'estado',
        'motivo_anulacion',
        'hash_sunat',
        'codigo_sunat',
        'xml_firmado',
        'cdr_respuesta',
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'sub_total' => 'decimal:2',
        'igv' => 'decimal:2',
        'total' => 'decimal:2',
        'correlativo' => 'integer',
    ];

    /**
     * Relación con venta
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    /**
     * Relación con serie de comprobante
     */
    public function serieComprobante(): BelongsTo
    {
        return $this->belongsTo(SerieComprobante::class);
    }

    /**
     * Relación con comprobante relación
     */
    public function comprobanteRelaciones(): HasMany
    {
        return $this->hasMany(ComprobanteRelacion::class, 'comprobante_id');
    }
}
