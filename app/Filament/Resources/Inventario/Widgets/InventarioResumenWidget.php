<?php

namespace App\Filament\Resources\Inventario\Widgets;

use App\Models\Producto;
use App\Models\MovimientoInventario;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventarioResumenWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        // Total de productos activos
        $totalProductos = Producto::where('estado', 'activo')->count();

        // Total de unidades en inventario
        $totalUnidades = Producto::where('estado', 'activo')->sum('stock_total');

        // Total de movimientos
        $totalMovimientos = MovimientoInventario::count();

        // Movimientos hoy
        $movimientosHoy = MovimientoInventario::whereDate('fecha_movimiento', Carbon::today())->count();

        // Variación de stock de ayer a hoy
        $stockAyer = MovimientoInventario::whereDate('fecha_movimiento', Carbon::yesterday())
            ->selectRaw('SUM(CASE WHEN tipo = "entrada" THEN cantidad_movimiento WHEN tipo = "salida" THEN -cantidad_movimiento ELSE 0 END) as variacion')
            ->value('variacion') ?? 0;

        $stockHoy = MovimientoInventario::whereDate('fecha_movimiento', Carbon::today())
            ->selectRaw('SUM(CASE WHEN tipo = "entrada" THEN cantidad_movimiento WHEN tipo = "salida" THEN -cantidad_movimiento ELSE 0 END) as variacion')
            ->value('variacion') ?? 0;

        // Calcular tendencia
        $tendenciaStock = $stockHoy - $stockAyer;
        $descripcionTendencia = $tendenciaStock > 0 
            ? '+' . number_format($tendenciaStock, 0) . ' unidades más que ayer'
            : ($tendenciaStock < 0 
                ? number_format(abs($tendenciaStock), 0) . ' unidades menos que ayer'
                : 'Sin cambios respecto a ayer');

        return [
            Stat::make('Total de Productos', number_format($totalProductos, 0))
                ->description('Productos activos en el catálogo')
                ->descriptionIcon('heroicon-o-cube')
                ->color('info')
                ->chart([10, 15, 12, 18, 20, 25, $totalProductos]),

            Stat::make('Unidades en Stock', number_format($totalUnidades, 0))
                ->description($descripcionTendencia)
                ->descriptionIcon($tendenciaStock >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($tendenciaStock >= 0 ? 'success' : 'warning')
                ->chart([100, 150, 120, 180, 200, 250, $totalUnidades]),

            Stat::make('Movimientos Hoy', number_format($movimientosHoy, 0))
                ->description('Total histórico: ' . number_format($totalMovimientos, 0))
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('primary')
                ->chart([5, 10, 8, 12, 15, 20, $movimientosHoy]),
        ];
    }
}
