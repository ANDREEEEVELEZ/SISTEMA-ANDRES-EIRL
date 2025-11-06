<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Barryvdh\DomPDF\Facade\Pdf;

class VentaExportController extends Controller
{
    public function export(Request $request)
    {
        // Construir query base
        $query = Venta::with(['cliente', 'comprobantes', 'detalleVentas.producto']);

        // NO excluimos ventas anuladas del query principal
        // Las mostraremos todas y solo las excluiremos de los totales

        // Filtrar por tipo de comprobante
        if ($request->tipo_comprobante && $request->tipo_comprobante !== 'todos') {
            $query->whereHas('comprobantes', function ($q) use ($request) {
                $q->where('tipo', $request->tipo_comprobante);
            });
        }

        // Filtrar por rango de fechas
        if ($request->fecha_inicio && $request->fecha_fin) {
            $query->whereBetween('fecha_venta', [
                $request->fecha_inicio,
                $request->fecha_fin . ' 23:59:59'
            ]);
        }

        // Filtrar por tipo de cliente (DNI/RUC)
        if ($request->tipo_cliente && $request->tipo_cliente !== 'todos') {
            $query->whereHas('cliente', function ($q) use ($request) {
                if ($request->tipo_cliente === 'dni') {
                    $q->where('tipo_doc', 'DNI');
                } elseif ($request->tipo_cliente === 'ruc') {
                    $q->where('tipo_doc', 'RUC');
                }
            });
        }

        // Filtrar por estado de comprobante
        if ($request->estado_comprobante && $request->estado_comprobante !== 'todos') {
            $query->whereHas('comprobantes', function ($q) use ($request) {
                $q->where('estado', $request->estado_comprobante);
            });
        }

        // Ordenar por fecha descendente
        $ventas = $query->orderBy('fecha_venta', 'desc')->get();

        // Preparar datos para el PDF
        $filtros = [
            'tipo_comprobante' => $request->tipo_comprobante ?? 'todos',
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'tipo_cliente' => $request->tipo_cliente ?? 'todos',
            'estado_comprobante' => $request->estado_comprobante ?? 'todos',
        ];

        // Calcular totales (EXCLUIR ventas anuladas, no restarlas)
        // Solo sumar las ventas que NO están anuladas
        $totalGeneral = 0;
        $subtotalGeneral = 0;
        $igvGeneral = 0;

        foreach ($ventas as $venta) {
            if ($venta->estado_venta !== 'anulada') {
                $totalGeneral += (float)$venta->total_venta;
                $subtotalGeneral += (float)$venta->subtotal_venta;
                $igvGeneral += (float)$venta->igv;
            }
        }

        // Contar ventas anuladas para mostrar en el reporte
        $cantidadAnuladas = $ventas->where('estado_venta', '==', 'anulada')->count();
        $montoAnulado = $ventas->where('estado_venta', '==', 'anulada')->sum('total_venta');

        // Generar PDF
        $pdf = Pdf::loadView('reportes.ventas_export', [
            'ventas' => $ventas,
            'filtros' => $filtros,
            'totalGeneral' => $totalGeneral,
            'subtotalGeneral' => $subtotalGeneral,
            'igvGeneral' => $igvGeneral,
            'cantidadAnuladas' => $cantidadAnuladas,
            'montoAnulado' => $montoAnulado,
            'fechaGeneracion' => now(),
        ]);

        $pdf->setPaper('a4', 'landscape');

        $filename = 'ventas_' . date('Y-m-d_His') . '.pdf';

        return $pdf->stream($filename);
    }
}
