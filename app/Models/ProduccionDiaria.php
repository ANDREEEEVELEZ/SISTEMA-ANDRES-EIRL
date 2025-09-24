<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProduccionDiaria extends Model
{
    use HasFactory;

    protected $table = 'produccion_diaria';

    protected $fillable = [
        'producto_id',
        'cantidad_diaria',
        'fecha_produccion',
    ];

    protected $casts = [
        'cantidad_diaria' => 'integer',
        'fecha_produccion' => 'date',
    ];

    /**
     * Relación con producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}