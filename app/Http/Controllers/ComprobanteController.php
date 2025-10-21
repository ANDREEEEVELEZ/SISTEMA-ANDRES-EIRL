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
}
