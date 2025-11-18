<?php

namespace App\Filament\Resources\Ventas\Widgets;

use App\Models\Venta;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class EstadisticasVentasWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Determinar rango a usar para los cálculos: por defecto HOY, o el rango pasado por GET
        $fechaInicioParam = request()->query('fecha_inicio');
        $fechaFinParam = request()->query('fecha_fin');

        try {
            if ($fechaInicioParam && $fechaFinParam) {
                $start = Carbon::parse($fechaInicioParam)->startOfDay();
                $end = Carbon::parse($fechaFinParam)->endOfDay();
            } elseif ($fechaInicioParam) {
                $start = Carbon::parse($fechaInicioParam)->startOfDay();
                $end = Carbon::parse($fechaInicioParam)->endOfDay();
            } elseif ($fechaFinParam) {
                $start = Carbon::parse($fechaFinParam)->startOfDay();
                $end = Carbon::parse($fechaFinParam)->endOfDay();
            } else {
                $start = Carbon::now()->startOfDay();
                $end = Carbon::now()->endOfDay();
            }
        } catch (\Exception $e) {
            $start = Carbon::now()->startOfDay();
            $end = Carbon::now()->endOfDay();
        }

        // Determinar si el usuario es super_admin (ve todo) o debe limitarse a sus ventas
        $esSuperAdmin = Auth::check() && optional(Auth::user())->hasRole('super_admin');

        // Total de ventas en el rango (excluir anuladas)
        $queryTotal = Venta::whereBetween('fecha_venta', [$start, $end])
            ->where('estado_venta', '!=', 'anulada');

        if (! $esSuperAdmin) {
            $queryTotal->where('user_id', Auth::id());
        }

        $totalVentas = $queryTotal->sum('total_venta');

        // Total de facturas del mes (excluir anuladas y verificar que comprobante esté emitido)
        $queryFacturas = Venta::whereBetween('fecha_venta', [$start, $end])
            ->where('estado_venta', '!=', 'anulada')
            ->whereHas('comprobantes', function ($query) {
                $query->where('tipo', 'factura')
                    ->where('estado', 'emitido');
            });

        if (! $esSuperAdmin) {
            $queryFacturas->where('user_id', Auth::id());
        }

        $totalFacturas = $queryFacturas->sum('total_venta');

        // Total de boletas del mes (excluir anuladas y verificar que comprobante esté emitido)
        $queryBoletas = Venta::whereBetween('fecha_venta', [$start, $end])
            ->where('estado_venta', '!=', 'anulada')
            ->whereHas('comprobantes', function ($query) {
                $query->where('tipo', 'boleta')
                    ->where('estado', 'emitido');
            });

        if (! $esSuperAdmin) {
            $queryBoletas->where('user_id', Auth::id());
        }

        $totalBoletas = $queryBoletas->sum('total_venta');

        // Total de tickets del mes (excluir anuladas y verificar que comprobante esté emitido)
        $queryTickets = Venta::whereBetween('fecha_venta', [$start, $end])
            ->where('estado_venta', '!=', 'anulada')
            ->whereHas('comprobantes', function ($query) {
                $query->where('tipo', 'ticket')
                    ->where('estado', 'emitido');
            });

        if (! $esSuperAdmin) {
            $queryTickets->where('user_id', Auth::id());
        }

        $totalTickets = $queryTickets->sum('total_venta');

        // Cantidad de ventas para mostrar en descripción (excluir anuladas)
        $queryCantidad = Venta::whereBetween('fecha_venta', [$start, $end])
            ->where('estado_venta', '!=', 'anulada');

        if (! $esSuperAdmin) {
            $queryCantidad->where('user_id', Auth::id());
        }

        $cantidadVentas = $queryCantidad->count();

        $queryCantidadFacturas = Venta::whereBetween('fecha_venta', [$start, $end])
            ->where('estado_venta', '!=', 'anulada')
            ->whereHas('comprobantes', function ($query) {
                $query->where('tipo', 'factura')
                    ->where('estado', 'emitido');
            });

        if (! $esSuperAdmin) {
            $queryCantidadFacturas->where('user_id', Auth::id());
        }

        $cantidadFacturas = $queryCantidadFacturas->count();

        $queryCantidadBoletas = Venta::whereBetween('fecha_venta', [$start, $end])
            ->where('estado_venta', '!=', 'anulada')
            ->whereHas('comprobantes', function ($query) {
                $query->where('tipo', 'boleta')
                    ->where('estado', 'emitido');
            });

        if (! $esSuperAdmin) {
            $queryCantidadBoletas->where('user_id', Auth::id());
        }

        $cantidadBoletas = $queryCantidadBoletas->count();

        $queryCantidadTickets = Venta::whereBetween('fecha_venta', [$start, $end])
            ->where('estado_venta', '!=', 'anulada')
            ->whereHas('comprobantes', function ($query) {
                $query->where('tipo', 'ticket')
                    ->where('estado', 'emitido');
            });

        if (! $esSuperAdmin) {
            $queryCantidadTickets->where('user_id', Auth::id());
        }

        $cantidadTickets = $queryCantidadTickets->count();

        // Formatear texto descriptivo según rango seleccionado
        if (! $fechaInicioParam && ! $fechaFinParam) {
            // Sin filtro: mostrar que son datos de HOY
            $fechaLabel = 'hoy (' . Carbon::now()->format('d/m/Y') . ')';
        } else {
            if ($start->toDateString() === $end->toDateString()) {
                $fechaLabel = 'hoy (' . $start->format('d/m/Y') . ')';
            } elseif ($start->copy()->startOfMonth()->equalTo($start) && $end->copy()->endOfMonth()->equalTo($end) && $start->month === $end->month && $start->year === $end->year) {
                $fechaLabel = $start->locale('es')->translatedFormat('F Y');
            } else {
                $fechaLabel = 'del ' . $start->format('d/m/Y') . ' al ' . $end->format('d/m/Y');
            }
        }

        return [
            Stat::make('Total de Ventas', 'S/ ' . number_format($totalVentas, 2))
                ->description("{$cantidadVentas} ventas " . $fechaLabel)
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Total Facturas', 'S/ ' . number_format($totalFacturas, 2))
                ->description("{$cantidadFacturas} facturas emitidas " . $fechaLabel)
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary')
                ->chart([2, 5, 3, 8, 6, 12, 9]),

            Stat::make('Total Boletas', 'S/ ' . number_format($totalBoletas, 2))
                ->description("{$cantidadBoletas} boletas emitidas " . $fechaLabel)
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('warning')
                ->chart([3, 8, 5, 12, 7, 9, 11]),

            Stat::make('Total Tickets', 'S/ ' . number_format($totalTickets, 2))
                ->description("{$cantidadTickets} tickets emitidos " . $fechaLabel)
                ->descriptionIcon('heroicon-m-ticket')
                ->color('info')
                ->chart([1, 3, 2, 6, 4, 8, 5]),
        ];
    }
}
