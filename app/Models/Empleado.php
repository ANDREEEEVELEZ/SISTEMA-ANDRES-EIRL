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
        'foto_facial_path',
        'face_descriptors',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_incorporacion' => 'date',
    ];

    /**
     * Accessor para obtener el nombre completo
     */
    public function getNombreAttribute()
    {
        return $this->nombres;
    }

    /**
     * Accessor para obtener el apellido
     */
    public function getApellidoAttribute()
    {
        return $this->apellidos;
    }

    /**
     * Accessor para obtener el nombre completo
     */
    public function getNombreCompletoAttribute()
    {
        return $this->nombres . ' ' . $this->apellidos;
    }

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