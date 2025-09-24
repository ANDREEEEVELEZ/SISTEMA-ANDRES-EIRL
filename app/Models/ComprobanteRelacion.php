<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComprobanteRelacion extends Model
{
    use HasFactory;

    protected $table = 'comprobante_relacion';

    protected $fillable = [
        'comprobante_origen_id',
        'comprobante_relacionado_id',
        'tipo_relacion',
    ];

    /**
     * Relación con comprobante origen
     */
    public function comprobanteOrigen(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class, 'comprobante_origen_id');
    }

    /**
     * Relación con comprobante relacionado
     */
    public function comprobanteRelacionado(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class, 'comprobante_relacionado_id');
    }
}