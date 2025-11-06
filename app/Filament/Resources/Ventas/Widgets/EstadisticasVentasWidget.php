<?php

namespace App\Filament\Resources\Ventas\Widgets;

use App\Models\Venta;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EstadisticasVentasWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $mesActual = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();

        // Total de ventas del mes (excluir anuladas)
        $totalVentas = Venta::whereBetween('fecha_venta', [$mesActual, $finMes])
            ->where('estado_venta', '!=', 'anulada')
            ->sum('total_venta');

        // Total de facturas del mes (excluir anuladas y verificar que comprobante esté emitido)
        $totalFacturas = Venta::whereBetween('fecha_venta', [$mesActual, $finMes])
            ->where('estado_venta', '!=', 'anulada')
            ->whereHas('comprobantes', function ($query) {
                $query->where('tipo', 'factura')
                    ->where('estado', 'emitido');
            })
            ->sum('total_venta');

        // Total de boletas del mes (excluir anuladas y verificar que comprobante esté emitido)
        $totalBoletas = Venta::whereBetween('fecha_venta', [$mesActual, $finMes])
            ->where('estado_venta', '!=', 'anulada')
            ->whereHas('comprobantes', function ($query) {
                $query->where('tipo', 'boleta')
                    ->where('estado', 'emitido');
            })
            ->sum('total_venta');

        // Total de tickets del mes (excluir anuladas y verificar que comprobante esté emitido)
        $totalTickets = Venta::whereBetween('fecha_venta', [$mesActual, $finMes])
            ->where('estado_venta', '!=', 'anulada')
            ->whereHas('comprobantes', function ($query) {
                $query->where('tipo', 'ticket')
                    ->where('estado', 'emitido');
            })
            ->sum('total_venta');

        // Cantidad de ventas para mostrar en descripción (excluir anuladas)
        $cantidadVentas = Venta::whereBetween('fecha_venta', [$mesActual, $finMes])
            ->where('estado_venta', '!=', 'anulada')
            ->count();

        $cantidadFacturas = Venta::whereBetween('fecha_venta', [$mesActual, $finMes])
            ->where('estado_venta', '!=', 'anulada')
            ->whereHas('comprobantes', function ($query) {
                $query->where('tipo', 'factura')
                    ->where('estado', 'emitido');
            })
            ->count();

        $cantidadBoletas = Venta::whereBetween('fecha_venta', [$mesActual, $finMes])
            ->where('estado_venta', '!=', 'anulada')
            ->whereHas('comprobantes', function ($query) {
                $query->where('tipo', 'boleta')
                    ->where('estado', 'emitido');
            })
            ->count();

        $cantidadTickets = Venta::whereBetween('fecha_venta', [$mesActual, $finMes])
            ->where('estado_venta', '!=', 'anulada')
            ->whereHas('comprobantes', function ($query) {
                $query->where('tipo', 'ticket')
                    ->where('estado', 'emitido');
            })
            ->count();

        return [
            Stat::make('Total de Ventas', 'S/ ' . number_format($totalVentas, 2))
                ->description("{$cantidadVentas} ventas en " . ucfirst(Carbon::now()->locale('es')->translatedFormat('F Y')))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Total Facturas', 'S/ ' . number_format($totalFacturas, 2))
                ->description("{$cantidadFacturas} facturas emitidas")
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary')
                ->chart([2, 5, 3, 8, 6, 12, 9]),

            Stat::make('Total Boletas', 'S/ ' . number_format($totalBoletas, 2))
                ->description("{$cantidadBoletas} boletas emitidas")
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('warning')
                ->chart([3, 8, 5, 12, 7, 9, 11]),

            Stat::make('Total Tickets', 'S/ ' . number_format($totalTickets, 2))
                ->description("{$cantidadTickets} tickets emitidos")
                ->descriptionIcon('heroicon-m-ticket')
                ->color('info')
                ->chart([1, 3, 2, 6, 4, 8, 5]),
        ];
    }
}
