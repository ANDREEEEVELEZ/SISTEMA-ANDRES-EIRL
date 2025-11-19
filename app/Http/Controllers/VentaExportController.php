<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class VentaExportController extends Controller
{
    public function export(Request $request)
    {

        $query = Venta::with(['cliente', 'comprobantes', 'detalleVentas.producto']);

        $esSuperAdmin = Auth::check() && optional(Auth::user())->hasRole('super_admin');
        if (! $esSuperAdmin) {
            $query->where('user_id', Auth::id());
        }

        if ($request->tipo_comprobante && $request->tipo_comprobante !== 'todos') {
            $query->whereHas('comprobantes', function ($q) use ($request) {
                $q->where('tipo', $request->tipo_comprobante);
            });
        }

        if ($request->fecha_inicio && $request->fecha_fin) {
            $query->whereBetween('fecha_venta', [
                $request->fecha_inicio,
                $request->fecha_fin . ' 23:59:59'
            ]);
        }

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

        // Preparar estadísticas tipo widget (respetando filtros aplicados)
        $ventasNoAnuladas = $ventas->filter(function ($v) {
            return ($v->estado_venta ?? '') !== 'anulada';
        });

        $cantidadVentas = $ventasNoAnuladas->count();
        $totalVentas = $ventasNoAnuladas->sum(function ($v) { return (float)$v->total_venta; });

        $ventasFacturas = $ventasNoAnuladas->filter(function ($v) {
            return $v->comprobantes->contains(function ($c) {
                return ($c->tipo ?? '') === 'factura' && ($c->estado ?? '') === 'emitido';
            });
        });
        $cantidadFacturas = $ventasFacturas->count();
        $totalFacturas = $ventasFacturas->sum(function ($v) { return (float)$v->total_venta; });

        $ventasBoletas = $ventasNoAnuladas->filter(function ($v) {
            return $v->comprobantes->contains(function ($c) {
                return ($c->tipo ?? '') === 'boleta' && ($c->estado ?? '') === 'emitido';
            });
        });
        $cantidadBoletas = $ventasBoletas->count();
        $totalBoletas = $ventasBoletas->sum(function ($v) { return (float)$v->total_venta; });

        $ventasTickets = $ventasNoAnuladas->filter(function ($v) {
            return $v->comprobantes->contains(function ($c) {
                return ($c->tipo ?? '') === 'ticket' && ($c->estado ?? '') === 'emitido';
            });
        });
        $cantidadTickets = $ventasTickets->count();
        $totalTickets = $ventasTickets->sum(function ($v) { return (float)$v->total_venta; });

        // Estadísticas de ventas anuladas (por tipo)
        $ventasAnuladas = $ventas->filter(function ($v) {
            return ($v->estado_venta ?? '') === 'anulada';
        });

        $cantidadVentasAnuladas = $ventasAnuladas->count();
        $totalVentasAnuladas = $ventasAnuladas->sum(function ($v) { return (float)$v->total_venta; });

        $ventasFacturasAnuladas = $ventasAnuladas->filter(function ($v) {
            return $v->comprobantes->contains(function ($c) {
                return ($c->tipo ?? '') === 'factura';
            });
        });
        $cantidadFacturasAnuladas = $ventasFacturasAnuladas->count();
        $totalFacturasAnuladas = $ventasFacturasAnuladas->sum(function ($v) { return (float)$v->total_venta; });

        $ventasBoletasAnuladas = $ventasAnuladas->filter(function ($v) {
            return $v->comprobantes->contains(function ($c) {
                return ($c->tipo ?? '') === 'boleta';
            });
        });
        $cantidadBoletasAnuladas = $ventasBoletasAnuladas->count();
        $totalBoletasAnuladas = $ventasBoletasAnuladas->sum(function ($v) { return (float)$v->total_venta; });

        $ventasTicketsAnuladas = $ventasAnuladas->filter(function ($v) {
            return $v->comprobantes->contains(function ($c) {
                return ($c->tipo ?? '') === 'ticket';
            });
        });
        $cantidadTicketsAnuladas = $ventasTicketsAnuladas->count();
        $totalTicketsAnuladas = $ventasTicketsAnuladas->sum(function ($v) { return (float)$v->total_venta; });

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
            // Stats para mostrar en reporte (similares a los widgets)
            'totalVentas' => $totalVentas,
            'cantidadVentas' => $cantidadVentas,
            'totalFacturas' => $totalFacturas,
            'cantidadFacturas' => $cantidadFacturas,
            'totalBoletas' => $totalBoletas,
            'cantidadBoletas' => $cantidadBoletas,
            'totalTickets' => $totalTickets,
            'cantidadTickets' => $cantidadTickets,
            // Stats anuladas por tipo
            'cantidadVentasAnuladas' => $cantidadVentasAnuladas ?? 0,
            'totalVentasAnuladas' => $totalVentasAnuladas ?? 0,
            'cantidadFacturasAnuladas' => $cantidadFacturasAnuladas ?? 0,
            'totalFacturasAnuladas' => $totalFacturasAnuladas ?? 0,
            'cantidadBoletasAnuladas' => $cantidadBoletasAnuladas ?? 0,
            'totalBoletasAnuladas' => $totalBoletasAnuladas ?? 0,
            'cantidadTicketsAnuladas' => $cantidadTicketsAnuladas ?? 0,
            'totalTicketsAnuladas' => $totalTicketsAnuladas ?? 0,
        ]);

        $pdf->setPaper('a4', 'landscape');

        $filename = 'ventas_' . date('Y-m-d_His') . '.pdf';


        return $pdf->download($filename);
    }
}
