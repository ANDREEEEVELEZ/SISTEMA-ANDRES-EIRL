<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SerieComprobante extends Model
{
    use HasFactory;

    protected $table = 'serie_comprobantes';

    protected $fillable = [
        'tipo',
        'serie',
        'ultimo_numero',
    ];

    protected $casts = [
        'tipo' => 'string',
    ];

    /**
     * Relación con comprobantes
     */
    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class, 'serie_comprobante_id');
    }
}