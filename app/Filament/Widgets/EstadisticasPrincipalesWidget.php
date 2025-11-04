<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Cliente;
use App\Models\Venta;
use App\Models\MovimientoCaja;
use Carbon\Carbon;

class EstadisticasPrincipalesWidget extends BaseWidget
{
    protected static ?int $sort = 1; // Segunda fila

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $hoy = Carbon::today();
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();

        // Ventas de hoy
        $ventasHoy = Venta::where('estado_venta', '!=', 'anulada')
            ->whereDate('fecha_venta', $hoy)
            ->count();

        $montoVentasHoy = Venta::where('estado_venta', '!=', 'anulada')
            ->whereDate('fecha_venta', $hoy)
            ->sum('total_venta');

        // Número y monto de ventas del mes (excluyendo anuladas)
        $ventasMes = Venta::where('estado_venta', '!=', 'anulada')
            ->whereBetween('fecha_venta', [$inicioMes, $finMes])
            ->count();

        $ventasMesMonto = Venta::where('estado_venta', '!=', 'anulada')
            ->whereBetween('fecha_venta', [$inicioMes, $finMes])
            ->sum('total_venta');

        // Ingresos en caja (movimientos tipo 'ingreso')
        $ingresosCajaMes = MovimientoCaja::where('tipo', 'ingreso')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->sum('monto');

        // Total de egresos del mes
        $egresosMes = MovimientoCaja::where('tipo', 'egreso')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->sum('monto');

        // Flujo neto = (Ventas + Ingresos en caja) - Egresos
        $flujoNeto = ($ventasMesMonto + $ingresosCajaMes) - $egresosMes;

        return [
            // Ahora en este widget mostramos "Ventas del Mes" (cantidad | monto)
            Stat::make('Ventas del Mes', $ventasMes . ' | S/ ' . number_format($ventasMesMonto, 2))
                ->description('Ventas en ' . ucfirst(Carbon::now()->locale('es')->translatedFormat('F Y')))
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 4, 3]),

            Stat::make('Ingresos del Mes', 'S/ ' . number_format($ingresosCajaMes, 2))
                ->description('Total ingresos')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Gastos del Mes', 'S/ ' . number_format($egresosMes, 2))
                ->description('Total egresos')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Flujo Neto del Mes', 'S/ ' . number_format($flujoNeto, 2))
                ->description('Ventas + Ingresos - Egresos')
                ->descriptionIcon($flujoNeto >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($flujoNeto >= 0 ? 'success' : 'danger'),
        ];
    }
}
