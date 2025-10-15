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
    protected static ?int $sort = 0; // Primero en aparecer

    protected function getStats(): array
    {
        // Total de clientes activos
        $totalClientes = Cliente::where('estado', 'activo')->count();

        // Número de ventas del mes actual (excluyendo anuladas)
        $ventasMes = Venta::where('estado_venta', '!=', 'anulada')
            ->whereMonth('fecha_venta', Carbon::now()->month)
            ->whereYear('fecha_venta', Carbon::now()->year)
            ->count();

        // Total de ingresos del mes actual (excluyendo anuladas)
        $ingresosMes = Venta::where('estado_venta', '!=', 'anulada')
            ->whereMonth('fecha_venta', Carbon::now()->month)
            ->whereYear('fecha_venta', Carbon::now()->year)
            ->sum('total_venta');

        // Total de gastos (movimientos de caja con tipo 'egreso')
        $gastosMes = MovimientoCaja::where('tipo', 'egreso')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('monto');

        return [
            Stat::make('Total Clientes', $totalClientes)
                ->description('Clientes activos')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Ventas del Mes', $ventasMes)
                ->description('Ventas en ' . Carbon::now()->format('F Y'))
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),

            Stat::make('Ingresos del Mes', 'S/ ' . number_format($ingresosMes, 2))
                ->description('Total ingresos')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Gastos del Mes', 'S/ ' . number_format($gastosMes, 2))
                ->description('Total egresos')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
        ];
    }
}
