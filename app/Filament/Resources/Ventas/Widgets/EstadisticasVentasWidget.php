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

        // Total de ventas del mes
        $totalVentas = Venta::whereBetween('fecha_venta', [$mesActual, $finMes])
            ->where('estado_venta', 'emitida')
            ->sum('total_venta');

        // Total de facturas del mes
        $totalFacturas = Venta::whereBetween('fecha_venta', [$mesActual, $finMes])
            ->where('estado_venta', 'emitida')
            ->whereHas('comprobantes', function ($query) {
                $query->where('tipo', 'factura');
            })
            ->sum('total_venta');

        // Total de boletas del mes
        $totalBoletas = Venta::whereBetween('fecha_venta', [$mesActual, $finMes])
            ->where('estado_venta', 'emitida')
            ->whereHas('comprobantes', function ($query) {
                $query->where('tipo', 'boleta');
            })
            ->sum('total_venta');

        // Total de tickets del mes
        $totalTickets = Venta::whereBetween('fecha_venta', [$mesActual, $finMes])
            ->where('estado_venta', 'emitida')
            ->whereHas('comprobantes', function ($query) {
                $query->where('tipo', 'ticket');
            })
            ->sum('total_venta');

        // Cantidad de ventas para mostrar en descripción
        $cantidadVentas = Venta::whereBetween('fecha_venta', [$mesActual, $finMes])
            ->where('estado_venta', 'emitida')
            ->count();

        $cantidadFacturas = Venta::whereBetween('fecha_venta', [$mesActual, $finMes])
            ->where('estado_venta', 'emitida')
            ->whereHas('comprobantes', function ($query) {
                $query->where('tipo', 'factura');
            })
            ->count();

        $cantidadBoletas = Venta::whereBetween('fecha_venta', [$mesActual, $finMes])
            ->where('estado_venta', 'emitida')
            ->whereHas('comprobantes', function ($query) {
                $query->where('tipo', 'boleta');
            })
            ->count();

        $cantidadTickets = Venta::whereBetween('fecha_venta', [$mesActual, $finMes])
            ->where('estado_venta', 'emitida')
            ->whereHas('comprobantes', function ($query) {
                $query->where('tipo', 'ticket');
            })
            ->count();

        return [
            Stat::make('Total de Ventas', 'S/ ' . number_format($totalVentas, 2))
                ->description("{$cantidadVentas} ventas en " . Carbon::now()->format('F Y'))
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
