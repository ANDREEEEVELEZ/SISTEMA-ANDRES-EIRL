<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'tipo_doc',
        'tipo_cliente',
        'num_doc',
        'nombre_razon',
        'fecha_registro',
        'estado',
        'telefono',
        'direccion',
    ];

    protected $casts = [
        'fecha_registro' => 'date',
    ];

    /**
     * Relación con ventas
     */
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }

    /**
     * Relación con comprobantes
     */
    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class, 'cliente_id');
    }
}