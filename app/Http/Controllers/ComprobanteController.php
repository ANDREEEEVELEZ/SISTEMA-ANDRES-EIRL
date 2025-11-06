<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\Request;

class ComprobanteController extends Controller
{
    /**
     * Imprimir comprobante de venta
     *
     * @param int $id ID de la venta
     * @return \Illuminate\View\View
     */
    public function imprimirComprobante($id)
    {
        // Cargar la venta con todas sus relaciones necesarias
        $venta = Venta::with([
            'cliente',
            'detalleVentas.producto',
            'comprobantes.serieComprobante',
            'user',
            'caja'
        ])->findOrFail($id);

        // Verificar que la venta exista
        if (!$venta) {
            abort(404, 'Venta no encontrada');
        }

        // Obtener el comprobante asociado (si existe)
        $comprobante = $venta->comprobantes->first();

        // Datos de la empresa desde configuración
        $empresa = config('empresa');

        // Si tiene comprobante electrónico (Factura o Boleta), usar formato completo
        // Si es solo ticket interno, usar formato compacto
        if ($comprobante && in_array($comprobante->tipo, ['factura', 'boleta', 'nota_credito'])) {
            // Formato completo para documentos electrónicos (Facturas y Boletas)
            return view('comprobantes.ticket-termico', compact('venta', 'comprobante', 'empresa'));
        } else {
            // Formato compacto para tickets internos
            return view('comprobantes.ticket-compacto', compact('venta', 'comprobante', 'empresa'));
        }
    }

    /**
     * Imprimir ticket directo (sin comprobante electrónico)
     *
     * @param int $id ID de la venta
     * @return \Illuminate\View\View
     */
    public function imprimirTicket($id)
    {
        return $this->imprimirComprobante($id);
    }

    /**
     * Imprimir Nota de Crédito o Débito
     *
     * @param int $id ID del comprobante (nota)
     * @return \Illuminate\View\View
     */
    public function imprimirNota($id)
    {
        // Buscar el comprobante (nota) directamente
        $nota = \App\Models\Comprobante::with([
            'venta.cliente',
            'venta.detalleVentas.producto',
            'venta.user',
            'venta.caja',
            'serieComprobante'
        ])->findOrFail($id);

        // Verificar que sea una nota
        if (!in_array($nota->tipo, ['nota de credito', 'nota de debito'])) {
            abort(404, 'El comprobante especificado no es una nota.');
        }

        // Obtener el comprobante original (factura o boleta que se está anulando)
        $comprobanteOriginal = $nota->venta->comprobantes()
            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
            ->first();

        // Datos de la empresa
        $empresa = config('empresa');

        // Renderizar vista de nota de crédito/débito
        return view('comprobantes.nota-credito', compact('nota', 'comprobanteOriginal', 'empresa'));
    }
}

