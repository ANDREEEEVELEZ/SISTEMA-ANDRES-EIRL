<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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
        // 'cdr_respuesta', // ELIMINADO: solo usamos ruta_cdr (archivo en storage)
        'ruta_xml',
        'ruta_cdr',
        'fecha_envio_sunat',
        'intentos_envio',
        'error_envio',
        'ticket_sunat', // Para Resumen Diario de boletas (respuesta asíncrona)
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'fecha_envio_sunat' => 'datetime',
        'sub_total' => 'decimal:2',
        'igv' => 'decimal:2',
        'total' => 'decimal:2',
        'correlativo' => 'integer',
        'intentos_envio' => 'integer',
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
     * Relación con comprobante relación (cuando este comprobante es el origen)
     */
    public function comprobanteRelaciones(): HasMany
    {
        return $this->hasMany(ComprobanteRelacion::class, 'comprobante_origen_id');
    }

    /**
     * Relación con comprobante relación (cuando este comprobante es el relacionado)
     */
    public function comprobanteRelacionesRelacionado(): HasMany
    {
        return $this->hasMany(ComprobanteRelacion::class, 'comprobante_relacionado_id');
    }

    /**
     * ═══════════════════════════════════════════════════════════════
     * HELPERS PARA INTEGRACIÓN SUNAT (Solución Híbrida)
     * ═══════════════════════════════════════════════════════════════
     */

    /**
     * Obtiene la ruta completa al archivo XML firmado en storage.
     * Si no existe archivo, devuelve null (usar xml_firmado de BD).
     */
    public function getXmlPath(): ?string
    {
        if ($this->ruta_xml && Storage::exists($this->ruta_xml)) {
            return storage_path('app/' . $this->ruta_xml);
        }
        return null;
    }

    /**
     * Obtiene la ruta completa al archivo CDR (ZIP) en storage.
     * Si no existe archivo, devuelve null (usar cdr_respuesta de BD).
     */
    public function getCdrPath(): ?string
    {
        if ($this->ruta_cdr && Storage::exists($this->ruta_cdr)) {
            return storage_path('app/' . $this->ruta_cdr);
        }
        return null;
    }

    /**
     * Obtiene el contenido del XML firmado (prioridad: archivo > BD).
     * Útil para reenvíos y regeneración de documentos.
     */
    public function getXmlContent(): ?string
    {
        // Prioridad 1: Archivo en storage
        if ($this->ruta_xml && Storage::exists($this->ruta_xml)) {
            return Storage::get($this->ruta_xml);
        }

        // Prioridad 2: Campo TEXT en BD
        return $this->xml_firmado;
    }

    /**
     * Obtiene el contenido del CDR (prioridad: archivo > BD).
     */
    public function getCdrContent(): ?string
    {
        // Prioridad 1: Archivo en storage
        if ($this->ruta_cdr && Storage::exists($this->ruta_cdr)) {
            return Storage::get($this->ruta_cdr);
        }

        // Prioridad 2: Campo TEXT en BD
        return $this->cdr_respuesta;
    }

    /**
     * Determina si el comprobante debe intentar reenvío a SUNAT.
     *
     * @param int $maxIntentos Límite de reintentos (default: 5)
     * @return bool
     */
    public function shouldRetry(int $maxIntentos = 5): bool
    {
        return $this->intentos_envio < $maxIntentos
            && $this->estado !== 'emitido'
            && !empty($this->getXmlContent());
    }

    /**
     * Obtiene el estado descriptivo del envío a SUNAT.
     *
     * @return string Estado legible para humanos
     */
    public function getEstadoSunatAttribute(): string
    {
        if ($this->codigo_sunat === '0') {
            return ' Aceptado por SUNAT';
        }

        if ($this->codigo_sunat && (int)$this->codigo_sunat >= 2000 && (int)$this->codigo_sunat <= 3999) {
            return 'Rechazado por SUNAT';
        }

        if ($this->intentos_envio > 0 && !$this->codigo_sunat) {
            return 'Pendiente de envío (intentos: ' . $this->intentos_envio . ')';
        }

        if ($this->ticket_sunat) {
            return 'Consultando ticket: ' . $this->ticket_sunat;
        }

        return 'No enviado a SUNAT';
    }

    /**
     * Genera el nombre estándar del comprobante según SUNAT.
     * Formato: {RUC}-{Tipo}-{Serie}-{Correlativo}
     * Ejemplo: 20123456789-01-F003-123
     */
    public function getNombreComprobanteAttribute(): string
    {
        $ruc = config('empresa.ruc', '00000000000');
        $codigoTipo = $this->serieComprobante->codigo_tipo_comprobante ?? '00';

        return "{$ruc}-{$codigoTipo}-{$this->serie}-{$this->correlativo}";
    }
}
