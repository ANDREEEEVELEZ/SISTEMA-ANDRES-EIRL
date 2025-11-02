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

    protected $attributes = [
        'estado' => 'activo',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cliente) {
            if (!$cliente->fecha_registro) {
                $cliente->fecha_registro = now()->toDateString();
            }
            if (!$cliente->estado) {
                $cliente->estado = 'activo';
            }

            // Auto-determinar tipo_cliente si no está establecido
            if (!$cliente->tipo_cliente) {
                if ($cliente->tipo_doc === 'DNI') {
                    $cliente->tipo_cliente = 'natural';
                } elseif ($cliente->tipo_doc === 'RUC' && $cliente->num_doc) {
                    $prefijo = substr($cliente->num_doc, 0, 2);
                    if ($prefijo === '10') {
                        $cliente->tipo_cliente = 'natural_con_negocio';
                    } elseif ($prefijo === '20') {
                        $cliente->tipo_cliente = 'juridica';
                    } else {
                        $cliente->tipo_cliente = 'natural'; // Fallback por defecto
                    }
                } else {
                    $cliente->tipo_cliente = 'natural'; // Fallback por defecto
                }
            }
        });

        static::updating(function ($cliente) {
            // Auto-determinar tipo_cliente si se modifica el documento
            if ($cliente->isDirty(['tipo_doc', 'num_doc'])) {
                if ($cliente->tipo_doc === 'DNI') {
                    $cliente->tipo_cliente = 'natural';
                } elseif ($cliente->tipo_doc === 'RUC' && $cliente->num_doc) {
                    $prefijo = substr($cliente->num_doc, 0, 2);
                    if ($prefijo === '10') {
                        $cliente->tipo_cliente = 'natural_con_negocio';
                    } elseif ($prefijo === '20') {
                        $cliente->tipo_cliente = 'juridica';
                    }
                }
            }
        });
    }

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
