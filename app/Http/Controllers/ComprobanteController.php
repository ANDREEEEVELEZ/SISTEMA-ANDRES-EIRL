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

        return view('comprobantes.ticket-termico', compact('venta', 'comprobante', 'empresa'));
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
