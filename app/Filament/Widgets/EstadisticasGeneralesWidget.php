<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Cliente;
use App\Models\Venta;
use App\Models\MovimientoCaja;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EstadisticasGeneralesWidget extends BaseWidget
{
    protected static ?int $sort = 0; // Primero en aparecer - Primera fila

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
    // Total de clientes activos
    $totalClientes = Cliente::where('estado', 'activo')->count();

    // Clientes nuevos este mes
    $inicioMes = Carbon::now()->startOfMonth();
    $finMes = Carbon::now()->endOfMonth();
    $clientesNuevosMes = Cliente::whereBetween('created_at', [$inicioMes, $finMes])->count();

        // Número y monto de ventas del mes actual (excluyendo anuladas)
        $ventasMes = Venta::where('estado_venta', '!=', 'anulada')
            ->whereMonth('fecha_venta', Carbon::now()->month)
            ->whereYear('fecha_venta', Carbon::now()->year)
            ->count();

        $ventasMesMonto = Venta::where('estado_venta', '!=', 'anulada')
            ->whereMonth('fecha_venta', Carbon::now()->month)
            ->whereYear('fecha_venta', Carbon::now()->year)
            ->sum('total_venta');

        // Total de ingresos del mes: usar únicamente movimientos de caja tipo 'ingreso'
        $ingresosMes = MovimientoCaja::where('tipo', 'ingreso')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('monto');

        // Ventas totales (acumulado histórico)
        $ventasTotales = Venta::where('estado_venta', '!=', 'anulada')->count();

        // Total de gastos (movimientos de caja con tipo 'egreso')
        $gastosMes = MovimientoCaja::where('tipo', 'egreso')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('monto');

        // Calcular ventas de hoy para mostrar en este widget (se intercambió la posición)
        $hoy = Carbon::today();

        $ventasHoy = Venta::where('estado_venta', '!=', 'anulada')
            ->whereDate('fecha_venta', $hoy)
            ->count();

        $montoVentasHoy = Venta::where('estado_venta', '!=', 'anulada')
            ->whereDate('fecha_venta', $hoy)
            ->sum('total_venta');

        return [
            Stat::make('Total Clientes', $totalClientes)
                ->description('Clientes activos')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Clientes Nuevos', $clientesNuevosMes)
                ->description('Este mes')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success'),

            Stat::make('Ventas Totales', number_format($ventasTotales))
                ->description('Acumulado histórico')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            // Ahora en este widget mostramos "Ventas de Hoy" (cantidad | monto)
            Stat::make('Ventas de Hoy', $ventasHoy . ' | S/ ' . number_format($montoVentasHoy, 2))
                ->description('Ventas realizadas hoy')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),
        ];
    }
}
