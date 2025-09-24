<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empleado extends Model
{
    use HasFactory;

    protected $table = 'empleados';

    protected $fillable = [
        'user_id',
        'nombres',
        'apellidos',
        'dni',
        'telefono',
        'direccion',
        'fecha_nacimiento',
        'correo_empleado',
        'distrito',
        'fecha_incorporacion',
        'estado_empleado',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_incorporacion' => 'date',
    ];

    /**
     * Relación con usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con asistencias
     */
    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'empleado_id');
    }

    /**
     * Relación con producción diaria
     */
    public function produccionesDiarias(): HasMany
    {
        return $this->hasMany(ProduccionDiaria::class, 'empleado_id');
    }

    /**
     * Relación con ventas
     */
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'empleado_id');
    }
}