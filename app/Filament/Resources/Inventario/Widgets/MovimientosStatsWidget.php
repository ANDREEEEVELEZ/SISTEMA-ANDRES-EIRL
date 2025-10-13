<?php

namespace App\Filament\Resources\Inventario\Widgets;

use App\Models\MovimientoInventario;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MovimientosStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Movimientos del mes actual
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();

        // Contar movimientos por tipo este mes
        $entradasMes = MovimientoInventario::where('tipo', 'entrada')
            ->whereBetween('fecha_movimiento', [$inicioMes, $finMes])
            ->count();

        $salidasMes = MovimientoInventario::where('tipo', 'salida')
            ->whereBetween('fecha_movimiento', [$inicioMes, $finMes])
            ->count();

        $ajustesMes = MovimientoInventario::where('tipo', 'ajuste')
            ->whereBetween('fecha_movimiento', [$inicioMes, $finMes])
            ->count();

        // Cantidad total de unidades movidas este mes
        $unidadesEntrada = MovimientoInventario::where('tipo', 'entrada')
            ->whereBetween('fecha_movimiento', [$inicioMes, $finMes])
            ->sum('cantidad_movimiento');

        $unidadesSalida = MovimientoInventario::where('tipo', 'salida')
            ->whereBetween('fecha_movimiento', [$inicioMes, $finMes])
            ->sum('cantidad_movimiento');

        // Datos para el gráfico de tendencia (últimos 7 días)
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = Carbon::now()->subDays($i)->startOfDay();
            $chartData[] = MovimientoInventario::whereDate('fecha_movimiento', $fecha)->count();
        }

        return [
            Stat::make('Entradas del Mes', $entradasMes)
                ->description("{$unidadesEntrada} unidades ingresadas")
                ->descriptionIcon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->chart($chartData),

            Stat::make('Salidas del Mes', $salidasMes)
                ->description("{$unidadesSalida} unidades retiradas")
                ->descriptionIcon('heroicon-o-arrow-up-tray')
                ->color('danger')
                ->chart($chartData),

            Stat::make('Ajustes del Mes', $ajustesMes)
                ->description('Correcciones de inventario')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color('warning')
                ->chart($chartData),
        ];
    }
}
