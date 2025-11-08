<?php

namespace App\Services;

use App\Models\Comprobante;
use App\Models\Venta;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\Document;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\Note;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Model\Summary\Summary;
use Greenter\Model\Summary\SummaryDetail;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SunatService
{
    protected See $see;
    protected Company $company;

    public function __construct()
    {
        $this->initializeSee();
        $this->initializeCompany();
    }

    /**
     * ═══════════════════════════════════════════════════════════════
     * INICIALIZACIÓN DE GREENTER
     * ═══════════════════════════════════════════════════════════════
     */

    /**
     * Configura el objeto See de Greenter con certificado y credenciales.
     */
    protected function initializeSee(): void
    {
        $this->see = new See();

        // Cargar certificado (SIEMPRE desde la raíz del proyecto)
        $certPath = base_path('certificate.pem');

        if (!file_exists($certPath)) {
            throw new \Exception("Certificado no encontrado en: {$certPath}. Verifica que certificate.pem esté en la raíz del proyecto.");
        }

        $certContent = file_get_contents($certPath);
        if (!$certContent) {
            throw new \Exception("No se pudo leer el certificado desde: {$certPath}");
        }

        $this->see->setCertificate($certContent);

        // Configurar endpoint (BETA o PRODUCCIÓN)
        $mode = config('sunat.mode', 'BETA');
        $endpoint = $mode === 'PROD'
            ? SunatEndpoints::FE_PRODUCCION
            : SunatEndpoints::FE_BETA;
        $this->see->setService($endpoint);

        // Configurar credenciales Clave SOL
        $ruc = config('sunat.ruc', '20000000001');
        $user = config('sunat.user', 'MODDATOS');
        $pass = config('sunat.pass', 'moddatos');
        $this->see->setClaveSOL($ruc, $user, $pass);
    }

    /**
     * Inicializa datos de la empresa emisora (desde config).
     */
    protected function initializeCompany(): void
    {
        $address = (new Address())
            ->setUbigueo(config('empresa.ubigeo', '150101'))
            ->setDepartamento(config('empresa.departamento', 'LIMA'))
            ->setProvincia(config('empresa.provincia', 'LIMA'))
            ->setDistrito(config('empresa.distrito', 'LIMA'))
            ->setUrbanizacion(config('empresa.urbanizacion', '-'))
            ->setDireccion(config('empresa.direccion', 'Av. Principal 123'))
            ->setCodLocal('0000'); // Código de establecimiento (0000 = principal)

        $this->company = (new Company())
            ->setRuc(config('empresa.ruc', '20123456789'))
            ->setRazonSocial(config('empresa.razon_social', 'EMPRESA SAC'))
            ->setNombreComercial(config('empresa.nombre_comercial', 'EMPRESA'))
            ->setAddress($address);
    }


    /**
     * Envía una factura o boleta a SUNAT (respuesta inmediata).
     * Aplica lógica híbrida: guarda XML en BD temporalmente, y en archivo si es exitoso.
     *
     * @param Comprobante $comprobante
     * @return array ['success' => bool, 'message' => string, 'codigo' => string|null]
     */
    public function enviarFacturaBoleta(Comprobante $comprobante): array
    {
        try {
            // 0. Cargar relaciones necesarias (serieComprobante para nombre_comprobante)
            $comprobante->load('serieComprobante');

            // 1. Construir objeto Invoice de Greenter
            $invoice = $this->buildInvoiceFromComprobante($comprobante);

            // 2. Enviar a SUNAT (Greenter genera y firma internamente)
            $result = $this->see->send($invoice);

            // 3. Obtener XML firmado para guardar
            $xmlSigned = $this->see->getFactory()->getLastXml();

            // 4. SIEMPRE guardar XML en archivo (incluso si falla)
            $ruc = config('empresa.ruc', '00000000000');
            $codigoTipo = $comprobante->serieComprobante->codigo_tipo_comprobante ?? '00';
            $nombreBase = "{$ruc}-{$codigoTipo}-{$comprobante->serie}-{$comprobante->correlativo}";
            $xmlPath = "sunat/xml/{$nombreBase}.xml";
            Storage::disk('sunat')->put($xmlPath, $xmlSigned);

            // 5. Guardar datos en BD
            $comprobante->update([
                'xml_firmado' => $xmlSigned,  // Temporal en BD
                'ruta_xml' => $xmlPath,        // Permanente en archivo
                'fecha_envio_sunat' => now(),
            ]);
            $comprobante->increment('intentos_envio');

            // 6. Verificar conexión con SUNAT
            if (!$result->isSuccess()) {
                $error = $result->getError();
                $comprobante->update([
                    'error_envio' => $error->getCode() . ': ' . $error->getMessage(),
                ]);

                Log::error('Error al conectar con SUNAT', [
                    'comprobante_id' => $comprobante->id,
                    'error' => $error->getMessage(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Error de conexión: ' . $error->getMessage(),
                    'codigo' => $error->getCode(),
                ];
            }

            // 7. Procesar respuesta CDR
            /** @phpstan-ignore-next-line */
            $cdr = $result->getCdrResponse();
            $codigo = $cdr ? (int) $cdr->getCode() : null;

            // 8. Guardar CDR si existe
            $cdrPath = "sunat/cdr/R-{$nombreBase}.zip";
            $cdrZip = null;
            /** @phpstan-ignore-next-line */
            if ($result->getCdrZip()) {
                /** @phpstan-ignore-next-line */
                $cdrZip = $result->getCdrZip();
                Storage::disk('sunat')->put($cdrPath, $cdrZip);
            }

            // 9. Actualizar BD según código de respuesta
            if ($codigo === 0) {

                $comprobante->update([
                    'estado' => 'emitido',
                    'hash_sunat' => $cdr->getId(),
                    'codigo_sunat' => (string) $codigo,
                    // NO guardar cdr_respuesta (es binario y causa error MySQL)
                    // Solo guardar ruta, el CDR se lee del archivo cuando se necesita
                    'ruta_cdr' => $cdrPath,
                    'error_envio' => null,
                ]);

                // Actualizar estado de la venta
                $comprobante->venta->update(['estado_venta' => 'emitida']);

                $mensaje = $cdr->getDescription();
                if (count($cdr->getNotes()) > 0) {
                    $mensaje .= ' (CON OBSERVACIONES: ' . implode(', ', $cdr->getNotes()) . ')';
                }

                return [
                    'success' => true,
                    'message' => $mensaje,
                    'codigo' => '0',
                ];
            } elseif ($codigo >= 2000 && $codigo <= 3999) {

                $comprobante->update([
                    'estado' => 'rechazado',
                    'codigo_sunat' => (string) $codigo,
                    'xml_firmado' => null,
                    'ruta_xml' => $xmlPath,
                    'ruta_cdr' => $cdrPath,
                    'error_envio' => $cdr->getDescription(),
                ]);

                $comprobante->venta->update(['estado_venta' => 'rechazada']);

                return [
                    'success' => false,
                    'message' => 'RECHAZADO: ' . $cdr->getDescription(),
                    'codigo' => (string) $codigo,
                ];
            } else {
                // Código inusual (ni aceptado ni rechazo estándar)
                $comprobante->update([
                    'estado' => 'rechazado', // Marcar como rechazado
                    'codigo_sunat' => (string) $codigo,
                    'ruta_xml' => $xmlPath,
                    'ruta_cdr' => $cdrPath,
                    'error_envio' => 'Código inusual: ' . $cdr->getDescription(),
                ]);

                Log::warning('Código SUNAT inusual', [
                    'comprobante_id' => $comprobante->id,
                    'codigo' => $codigo,
                    'descripcion' => $cdr->getDescription(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Código inusual: ' . $cdr->getDescription(),
                    'codigo' => (string) $codigo,
                ];
            }
        } catch (\Exception $e) {
            // Error de excepción (SOAP, conexión, etc)
            // Intentar guardar XML si se generó antes del error
            try {
                $xmlSigned = $this->see->getFactory()->getLastXml();
                if ($xmlSigned) {
                    // Construir nombre manualmente (no depender del accessor)
                    $ruc = config('empresa.ruc', '00000000000');
                    $codigoTipo = $comprobante->serieComprobante->codigo_tipo_comprobante ?? '00';
                    $nombreBase = "{$ruc}-{$codigoTipo}-{$comprobante->serie}-{$comprobante->correlativo}";

                    $xmlPath = "sunat/xml/{$nombreBase}.xml";
                    Storage::disk('sunat')->put($xmlPath, $xmlSigned);

                    $comprobante->update([
                        'estado' => 'rechazado', // Marcar como rechazado por error
                        'xml_firmado' => $xmlSigned,
                        'ruta_xml' => $xmlPath,
                        'error_envio' => $e->getMessage(),
                        'fecha_envio_sunat' => now(),
                    ]);
                    $comprobante->increment('intentos_envio');
                } else {
                    // Si no hay XML, solo guardar el error
                    $comprobante->update([
                        'estado' => 'rechazado', // Marcar como rechazado por error
                        'error_envio' => $e->getMessage(),
                    ]);
                }
            } catch (\Exception $innerException) {
                // Si falla obtener XML, solo guardar error
                $comprobante->update([
                    'error_envio' => $e->getMessage() . ' | Inner: ' . $innerException->getMessage(),
                ]);
            }

            Log::error('Excepción al enviar comprobante', [
                'comprobante_id' => $comprobante->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Excepción: ' . $e->getMessage(),
                'codigo' => null,
            ];
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════
     * ENVÍO DE NOTAS DE CRÉDITO/DÉBITO
     * ═══════════════════════════════════════════════════════════════
     */

    /**
     * Envía una nota de crédito o débito a SUNAT (respuesta inmediata).
     *
     * @param Comprobante $nota
     * @return array ['success' => bool, 'message' => string, 'codigo' => string|null]
     */
    public function enviarNotaCredito(Comprobante $nota): array
    {
        try {
            // 0. Cargar relaciones necesarias (serieComprobante para nombre_comprobante)
            $nota->load('serieComprobante');

            // 1. Construir objeto Note de Greenter
            $noteObj = $this->buildNoteFromComprobante($nota);

            // 2. Enviar a SUNAT (Greenter genera y firma internamente)
            $result = $this->see->send($noteObj);

            // 3. Obtener XML firmado
            $xmlSigned = $this->see->getFactory()->getLastXml();

            // 4. SIEMPRE guardar XML en archivo (incluso si falla)
            $ruc = config('empresa.ruc', '00000000000');
            $codigoTipo = $nota->serieComprobante->codigo_tipo_comprobante ?? '00';
            $nombreBase = "{$ruc}-{$codigoTipo}-{$nota->serie}-{$nota->correlativo}";
            $xmlPath = "sunat/xml/{$nombreBase}.xml";
            Storage::disk('sunat')->put($xmlPath, $xmlSigned);

            // 5. Guardar datos en BD
            $nota->update([
                'xml_firmado' => $xmlSigned,
                'ruta_xml' => $xmlPath,
                'fecha_envio_sunat' => now(),
            ]);
            $nota->increment('intentos_envio');

            // 6. Verificar conexión
            if (!$result->isSuccess()) {
                $error = $result->getError();
                $nota->update([
                    'error_envio' => $error->getCode() . ': ' . $error->getMessage(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Error de conexión: ' . $error->getMessage(),
                    'codigo' => $error->getCode(),
                ];
            }

            // 7. Procesar CDR
            /** @phpstan-ignore-next-line */
            $cdr = $result->getCdrResponse();
            $codigo = (int) $cdr->getCode();

            // 8. Guardar CDR
            $cdrPath = "sunat/cdr/R-{$nombreBase}.zip";
            $cdrZip = null;
            /** @phpstan-ignore-next-line */
            if ($result->getCdrZip()) {
                /** @phpstan-ignore-next-line */
                $cdrZip = $result->getCdrZip();
                Storage::disk('sunat')->put($cdrPath, $cdrZip);
            }

            // 9. Actualizar BD
            if ($codigo === 0) {
                $nota->update([
                    'estado' => 'emitido',
                    'hash_sunat' => $cdr->getId(),
                    'codigo_sunat' => (string) $codigo,
                    // NO guardar cdr_respuesta (es binario y causa error MySQL)
                    'ruta_cdr' => $cdrPath,
                    'error_envio' => null,
                ]);

                return [
                    'success' => true,
                    'message' => $cdr->getDescription(),
                    'codigo' => '0',
                ];
            } elseif ($codigo >= 2000 && $codigo <= 3999) {
                $nota->update([
                    'estado' => 'rechazado',
                    'codigo_sunat' => (string) $codigo,
                    'xml_firmado' => null,
                    'ruta_xml' => $xmlPath,
                    'ruta_cdr' => $cdrPath,
                    'error_envio' => $cdr->getDescription(),
                ]);

                return [
                    'success' => false,
                    'message' => 'RECHAZADO: ' . $cdr->getDescription(),
                    'codigo' => (string) $codigo,
                ];
            }

            // Código inusual (ni aceptado ni rechazo estándar)
            $nota->update([
                'estado' => 'rechazado', // Marcar como rechazado
                'codigo_sunat' => (string) $codigo,
                'ruta_xml' => $xmlPath,
                'ruta_cdr' => $cdrPath,
                'error_envio' => 'Código inusual: ' . $cdr->getDescription(),
            ]);

            return [
                'success' => false,
                'message' => 'Código inusual: ' . $cdr->getDescription(),
                'codigo' => (string) $codigo,
            ];
        } catch (\Exception $e) {
            // Intentar guardar XML si se generó antes del error
            try {
                $xmlSigned = $this->see->getFactory()->getLastXml();
                if ($xmlSigned) {
                    // Construir nombre manualmente
                    $ruc = config('empresa.ruc', '00000000000');
                    $codigoTipo = $nota->serieComprobante->codigo_tipo_comprobante ?? '00';
                    $nombreBase = "{$ruc}-{$codigoTipo}-{$nota->serie}-{$nota->correlativo}";
                    $xmlPath = "sunat/xml/{$nombreBase}.xml";
                    Storage::disk('sunat')->put($xmlPath, $xmlSigned);

                    $nota->update([
                        'estado' => 'rechazado', // Marcar como rechazado por error
                        'xml_firmado' => $xmlSigned,
                        'ruta_xml' => $xmlPath,
                        'error_envio' => $e->getMessage(),
                        'fecha_envio_sunat' => now(),
                    ]);
                    $nota->increment('intentos_envio');
                } else {
                    $nota->update([
                        'estado' => 'rechazado', // Marcar como rechazado por error
                        'error_envio' => $e->getMessage()
                    ]);
                }
            } catch (\Exception $innerException) {
                $nota->update([
                    'estado' => 'rechazado', // Marcar como rechazado por error
                    'error_envio' => $e->getMessage() . ' | Inner: ' . $innerException->getMessage()
                ]);
            }

            return [
                'success' => false,
                'message' => 'Excepción: ' . $e->getMessage(),
                'codigo' => null,
            ];
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════
     * REENVÍO (Botón "Reenviar")
     * ═══════════════════════════════════════════════════════════════
     */

    /**
     * Reenvía un comprobante existente regenerándolo.
     *
     * @param Comprobante $comprobante
     * @return array
     */
    public function reenviarComprobante(Comprobante $comprobante): array
    {
        // Simplemente llamar al método de envío normal según el tipo
        if ($comprobante->tipo === 'factura' || $comprobante->tipo === 'boleta') {
            return $this->enviarFacturaBoleta($comprobante);
        } elseif ($comprobante->tipo === 'nota de credito' || $comprobante->tipo === 'nota de debito') {
            return $this->enviarNotaCredito($comprobante);
        }

        return [
            'success' => false,
            'message' => 'Tipo de comprobante no soportado',
            'codigo' => null,
        ];
    }

    /**
     * ═══════════════════════════════════════════════════════════════
     * BUILDERS (Convertir modelos Laravel → objetos Greenter)
     * ═══════════════════════════════════════════════════════════════
     */

    /**
     * Construye un objeto Invoice de Greenter desde un Comprobante.
     */
    protected function buildInvoiceFromComprobante(Comprobante $comprobante): Invoice
    {
        // Cargar relaciones necesarias
        $comprobante->load(['venta.detalleVentas.producto', 'venta.cliente']);

        $venta = $comprobante->venta;
        $cliente = $venta->cliente;

        // Cliente
        $client = new Client();
        if ($cliente) {
            $tipoDoc = $cliente->tipo_doc === 'DNI' ? '1' : '6'; // 1=DNI, 6=RUC
            $client->setTipoDoc($tipoDoc)
                ->setNumDoc($cliente->num_doc)
                ->setRznSocial($cliente->nombre_razon);
        } else {
            // Cliente genérico (venta sin cliente registrado)
            $client->setTipoDoc('1')
                ->setNumDoc('00000000')
                ->setRznSocial($venta->nombre_cliente_temporal ?? 'CLIENTE GENÉRICO');
        }

        // Determinar tipo de documento: 01=Factura, 03=Boleta
        $tipoDoc = $comprobante->tipo === 'factura' ? '01' : '03';

        // Invoice
        $invoice = (new Invoice())
            ->setUblVersion('2.1')
            ->setTipoOperacion('0101') // Hardcoded: Venta interna
            ->setTipoDoc($tipoDoc)
            ->setSerie($comprobante->serie)
            ->setCorrelativo((string) $comprobante->correlativo)
            ->setFechaEmision($comprobante->fecha_emision)
            ->setFormaPago(new FormaPagoContado())
            ->setTipoMoneda('PEN')
            ->setCompany($this->company)
            ->setClient($client)
            ->setMtoOperGravadas((float) $venta->subtotal_venta)
            ->setMtoIGV((float) $venta->igv)
            ->setTotalImpuestos((float) $venta->igv)
            ->setValorVenta((float) $venta->subtotal_venta)
            ->setSubTotal((float) $comprobante->total)
            ->setMtoImpVenta((float) $comprobante->total);

        // Detalles de venta
        $details = [];
        foreach ($venta->detalleVentas as $detalle) {
            $producto = $detalle->producto;
            $valorUnitario = (float) $detalle->precio_unitario / 1.18; // Sin IGV
            $valorVenta = (float) $detalle->subtotal / 1.18;
            $igv = (float) $detalle->subtotal - $valorVenta;

            $item = (new SaleDetail())
                ->setCodProducto((string) $producto->id)
                ->setUnidad('NIU') // Hardcoded: Unidad
                ->setCantidad($detalle->cantidad_venta)
                ->setMtoValorUnitario($valorUnitario)
                ->setDescripcion($producto->nombre_producto)
                ->setMtoBaseIgv($valorVenta)
                ->setPorcentajeIgv(18.00)
                ->setIgv($igv)
                ->setTipAfeIgv('10') // Hardcoded: Gravado
                ->setTotalImpuestos($igv)
                ->setMtoValorVenta($valorVenta)
                ->setMtoPrecioUnitario((float) $detalle->precio_unitario);

            $details[] = $item;
        }

        $invoice->setDetails($details);

        // Leyenda (monto en letras)
        $totalEnLetras = $this->convertirNumeroALetras((float) $comprobante->total);
        $legend = (new Legend())
            ->setCode('1000')
            ->setValue($totalEnLetras);

        $invoice->setLegends([$legend]);

        return $invoice;
    }

    /**
     * Construye un objeto Note de Greenter desde un Comprobante (nota de crédito).
     */
    protected function buildNoteFromComprobante(Comprobante $nota): Note
    {
        // Cargar relaciones necesarias
        $nota->load(['venta.detalleVentas.producto', 'venta.cliente']);

        $venta = $nota->venta;
        $cliente = $venta->cliente;

        // Cliente
        $client = new Client();
        if ($cliente) {
            $tipoDoc = $cliente->tipo_doc === 'DNI' ? '1' : '6';
            $client->setTipoDoc($tipoDoc)
                ->setNumDoc($cliente->num_doc)
                ->setRznSocial($cliente->nombre_razon);
        } else {
            $client->setTipoDoc('1')
                ->setNumDoc('00000000')
                ->setRznSocial($venta->nombre_cliente_temporal ?? 'CLIENTE GENÉRICO');
        }

        // Obtener comprobante origen (el que se está anulando/corrigiendo)
        // La nota es el comprobante_relacionado_id, no el origen
        $relacion = $nota->comprobanteRelacionesRelacionado()->first();
        $comprobanteOrigen = $relacion ? $relacion->comprobanteOrigen : null;

        if (!$comprobanteOrigen) {
            throw new \Exception('No se encontró el comprobante origen de la nota de crédito.');
        }

        $tipDocAfectado = $comprobanteOrigen->tipo === 'factura' ? '01' : '03';
        $numDocAfectado = $comprobanteOrigen->serie . '-' . $comprobanteOrigen->correlativo;

        // Note
        $noteObj = (new Note())
            ->setUblVersion('2.1')
            ->setTipoDoc('07') // Nota de Crédito
            ->setSerie($nota->serie)
            ->setCorrelativo((string) $nota->correlativo)
            ->setFechaEmision($nota->fecha_emision)
            ->setTipDocAfectado($tipDocAfectado)
            ->setNumDocfectado($numDocAfectado)
            ->setCodMotivo($nota->codigo_tipo_nota ?? '01') // Catálogo 09
            ->setDesMotivo($nota->motivo_anulacion ?? 'ANULACIÓN DE LA OPERACIÓN')
            ->setTipoMoneda('PEN')
            ->setCompany($this->company)
            ->setClient($client)
            ->setMtoOperGravadas((float) $venta->subtotal_venta)
            ->setMtoIGV((float) $venta->igv)
            ->setTotalImpuestos((float) $venta->igv)
            ->setMtoImpVenta((float) $nota->total);

        // Detalles
        $details = [];
        foreach ($venta->detalleVentas as $detalle) {
            $producto = $detalle->producto;
            $valorUnitario = (float) $detalle->precio_unitario / 1.18;
            $valorVenta = (float) $detalle->subtotal / 1.18;
            $igv = (float) $detalle->subtotal - $valorVenta;

            $item = (new SaleDetail())
                ->setCodProducto((string) $producto->id)
                ->setUnidad('NIU')
                ->setCantidad($detalle->cantidad_venta)
                ->setDescripcion($producto->nombre_producto)
                ->setMtoBaseIgv($valorVenta)
                ->setPorcentajeIgv(18.00)
                ->setIgv($igv)
                ->setTipAfeIgv('10')
                ->setTotalImpuestos($igv)
                ->setMtoValorVenta($valorVenta)
                ->setMtoValorUnitario($valorUnitario)
                ->setMtoPrecioUnitario((float) $detalle->precio_unitario);

            $details[] = $item;
        }

        $noteObj->setDetails($details);

        // Leyenda
        $totalEnLetras = $this->convertirNumeroALetras((float) $nota->total);
        $legend = (new Legend())
            ->setCode('1000')
            ->setValue($totalEnLetras);

        $noteObj->setLegends([$legend]);

        return $noteObj;
    }

    /**
     * ═══════════════════════════════════════════════════════════════
     * UTILIDADES
     * ═══════════════════════════════════════════════════════════════
     */

    /**
     * Convierte un número a letras (para leyenda de comprobantes).
     * Implementación básica - puedes mejorarla o usar una librería.
     */
    protected function convertirNumeroALetras(float $numero): string
    {
        $entero = (int) $numero;
        $decimales = (int) round(($numero - $entero) * 100);

        // Implementación simplificada (puedes usar una librería como luecano/numero-a-letras)
        $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        // Implementación básica (solo maneja hasta 999)
        if ($entero < 10) {
            $letras = $unidades[$entero];
        } elseif ($entero < 100) {
            $d = (int) ($entero / 10);
            $u = $entero % 10;
            $letras = $decenas[$d] . ($u > 0 ? ' Y ' . $unidades[$u] : '');
        } elseif ($entero < 1000) {
            $c = (int) ($entero / 100);
            $resto = $entero % 100;
            $letras = ($entero === 100 ? 'CIEN' : $centenas[$c]);
            if ($resto > 0) {
                $d = (int) ($resto / 10);
                $u = $resto % 10;
                if ($resto < 10) {
                    $letras .= ' ' . $unidades[$resto];
                } else {
                    $letras .= ' ' . $decenas[$d] . ($u > 0 ? ' Y ' . $unidades[$u] : '');
                }
            }
        } else {
            // Para números mayores, considera usar una librería
            $letras = number_format($entero, 0, '', ' ');
        }

        return 'SON ' . trim($letras) . ' CON ' . str_pad($decimales, 2, '0', STR_PAD_LEFT) . '/100 SOLES';
    }

    /**
     * ═══════════════════════════════════════════════════════════════
     * RESUMEN DIARIO DE BOLETAS
     * ═══════════════════════════════════════════════════════════════
     */

    /**
     * Envía un Resumen Diario agrupando boletas de una fecha específica.
     *
     * @param \DateTime $fechaEmision Fecha de emisión de las boletas a agrupar
     * @return array ['success' => bool, 'ticket' => string|null, 'message' => string, 'xml_path' => string|null]
     */
    public function enviarResumenDiario(\DateTime $fechaEmision): array
    {
        try {
            // Obtener boletas del día que NO tienen ticket_sunat (no enviadas en resumen)
            // y que NO estén anuladas (estado='anulado')
            // y que NO tengan Notas de Crédito relacionadas (anulación antes del envío)
            $boletas = Comprobante::with(['venta.cliente', 'serieComprobante'])
                ->whereHas('serieComprobante', function ($query) {
                    $query->where('codigo_tipo_comprobante', '03'); // Solo boletas
                })
                ->whereDate('fecha_emision', $fechaEmision->format('Y-m-d'))
                ->whereNull('ticket_sunat') // Solo las que no han sido enviadas en resumen
                ->where('estado', 'emitido') // ⚠️ CRÍTICO: Excluir anuladas
                ->whereDoesntHave('comprobanteRelacionesRelacionado', function ($query) {
                    // Excluir boletas que ya tienen NC creadas (anuladas antes del envío)
                    $query->whereHas('serieComprobante', function ($q) {
                        $q->where('codigo_tipo_comprobante', '07'); // Nota de Crédito
                    });
                })
                ->get();

            if ($boletas->isEmpty()) {
                return [
                    'success' => false,
                    'ticket' => null,
                    'message' => 'No hay boletas pendientes para la fecha ' . $fechaEmision->format('Y-m-d'),
                    'xml_path' => null,
                ];
            }

            Log::info("📦 Preparando Resumen Diario con {$boletas->count()} boletas de {$fechaEmision->format('Y-m-d')}");

            // Crear objeto Summary de Greenter
            $summary = new \Greenter\Model\Summary\Summary();

            // Obtener correlativo del resumen (basado en fecha)
            $correlativo = $this->obtenerCorrelativoResumen($fechaEmision);

            $summary->setFecGeneracion($fechaEmision) // Fecha de emisión de las boletas
                ->setFecResumen(new \DateTime()) // Fecha de envío del resumen (hoy)
                ->setCorrelativo(str_pad($correlativo, 3, '0', STR_PAD_LEFT))
                ->setCompany($this->company);

            // Agregar detalles de cada boleta
            $details = [];
            foreach ($boletas as $boleta) {
                $detail = new \Greenter\Model\Summary\SummaryDetail();

                // Construir serie-correlativo manualmente
                $serieCorrelativo = $boleta->serieComprobante->serie . '-' . $boleta->correlativo;

                $detail->setTipoDoc('03') // 03 = Boleta
                    ->setSerieNro($serieCorrelativo)
                    ->setEstado('1') // 1 = Adicionar, 3 = Anular
                    ->setClienteTipo($boleta->venta->cliente->tipo_documento ?? '1')
                    ->setClienteNro($boleta->venta->cliente->numero_documento ?? '00000000')
                    ->setTotal((float) $boleta->importe_total)
                    ->setMtoOperGravadas((float) $boleta->base_imponible)
                    ->setMtoIGV((float) $boleta->igv);

                $details[] = $detail;
            }

            $summary->setDetails($details);

            // Enviar a SUNAT
            $result = $this->see->send($summary);

            // Guardar XML del resumen
            $xmlContent = $this->see->getFactory()->getLastXml();
            $xmlFileName = $summary->getName() . '.xml';
            $xmlPath = 'xml/' . $xmlFileName;

            Storage::disk('sunat')->put($xmlPath, $xmlContent);

            if (!$result->isSuccess()) {
                $error = $result->getError();
                $errorMessage = "Código: {$error->getCode()} - {$error->getMessage()}";

                Log::error(" Error al enviar Resumen Diario: {$errorMessage}");

                return [
                    'success' => false,
                    'ticket' => null,
                    'message' => $errorMessage,
                    'xml_path' => $xmlPath,
                ];
            }

            // SUNAT devuelve un TICKET (respuesta asíncrona)
            $ticket = $result->getTicket();

            Log::info("Resumen Diario enviado. Ticket: {$ticket}");

            // Actualizar boletas con el ticket
            Comprobante::whereIn('id', $boletas->pluck('id'))
                ->update([
                    'ticket_sunat' => $ticket,
                    'fecha_envio_sunat' => now(),
                    'error_envio' => null,
                ]);

            return [
                'success' => true,
                'ticket' => $ticket,
                'message' => "Resumen enviado correctamente. Ticket: {$ticket}. Total boletas: {$boletas->count()}",
                'xml_path' => $xmlPath,
            ];

        } catch (\Exception $e) {
            Log::error("Excepción en enviarResumenDiario: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'ticket' => null,
                'message' => 'Error: ' . $e->getMessage(),
                'xml_path' => null,
            ];
        }
    }

    /**
     * Consulta el estado de un Resumen Diario usando el ticket de SUNAT.
     *
     * @param string $ticket Ticket devuelto por SUNAT al enviar el resumen
     * @return array ['success' => bool, 'codigo' => int|null, 'mensaje' => string, 'cdr_path' => string|null]
     */
    public function consultarTicketResumen(string $ticket): array
    {
        try {
            Log::info("🔍 Consultando estado del ticket: {$ticket}");

            $statusResult = $this->see->getStatus($ticket);

            if (!$statusResult->isSuccess()) {
                $error = $statusResult->getError();
                $errorMessage = "Código: {$error->getCode()} - {$error->getMessage()}";

                Log::warning("Ticket {$ticket} aún no procesado: {$errorMessage}");

                return [
                    'success' => false,
                    'codigo' => null,
                    'mensaje' => $errorMessage,
                    'cdr_path' => null,
                ];
            }

            // Obtener CDR (Constancia de Recepción)
            $cdr = $statusResult->getCdrResponse();
            $cdrZip = $statusResult->getCdrZip();

            $codigo = (int) $cdr->getCode();
            $mensaje = $cdr->getDescription();

            Log::info(" Ticket {$ticket} procesado. Código: {$codigo} - {$mensaje}");

            // Guardar CDR
            $cdrFileName = 'R-' . $ticket . '.zip';
            $cdrPath = 'cdr/' . $cdrFileName;
            Storage::disk('sunat')->put($cdrPath, $cdrZip);

            // Actualizar boletas asociadas al ticket
            $boletas = Comprobante::where('ticket_sunat', $ticket)->get();

            foreach ($boletas as $boleta) {
                $boleta->update([
                    'codigo_sunat' => (string) $codigo,
                    'ruta_cdr' => $cdrPath,
                    'error_envio' => ($codigo === 0) ? null : $mensaje,
                ]);
            }

            return [
                'success' => true,
                'codigo' => $codigo,
                'mensaje' => $mensaje,
                'cdr_path' => $cdrPath,
            ];

        } catch (\Exception $e) {
            Log::error(" Excepción en consultarTicketResumen: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'codigo' => null,
                'mensaje' => 'Error: ' . $e->getMessage(),
                'cdr_path' => null,
            ];
        }
    }

    /**
     * Obtiene el correlativo del resumen diario para una fecha específica.
     * El formato es: RC-YYYYMMDD-XXX donde XXX es el correlativo del día.
     */
    protected function obtenerCorrelativoResumen(\DateTime $fecha): int
    {
        // Buscar el último resumen del día (basado en ticket_sunat que tiene formato con fecha)
        $ultimoResumen = Comprobante::whereNotNull('ticket_sunat')
            ->whereDate('fecha_envio_sunat', $fecha->format('Y-m-d'))
            ->orderBy('id', 'desc')
            ->first();

        if (!$ultimoResumen) {
            return 1;
        }

        // Extraer correlativo del ticket (formato: YYYYMMDD-XXX)
        // Ticket ejemplo: "20231108-001"
        $partes = explode('-', $ultimoResumen->ticket_sunat);
        if (count($partes) >= 2) {
            return ((int) end($partes)) + 1;
        }

        return 1;
    }
}
