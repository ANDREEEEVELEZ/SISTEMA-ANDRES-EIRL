<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asistencia extends Model
{
    use HasFactory;

    protected $table = 'asistencias';

    protected $fillable = [
        'empleado_id',
        'fecha',
        'hora_entrada',
        'hora_salida',
        'estado',
        'observacion',
        'metodo_registro',
        'razon_manual',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_entrada' => 'datetime:H:i',
        'hora_salida' => 'datetime:H:i',
    ];

    /**
     * Obtener el método de registro en formato legible
     */
    public function getMetodoRegistroFormateadoAttribute(): string
    {
        return match($this->metodo_registro) {
            'facial' => '📷 Reconocimiento Facial',
            'manual_dni' => '📝 Manual (DNI)',
            default => 'Desconocido'
        };
    }

    /**
     * Verificar si es registro manual
     */
    public function esRegistroManual(): bool
    {
        return $this->metodo_registro === 'manual_dni';
    }

    /**
     * Relación con empleado
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }
}