<?php

namespace App\Filament\Widgets;

use App\Models\Producto;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $productosAgotados = Producto::agotados()->where('estado', 'activo')->count();
        $productosStockBajo = Producto::stockBajo()->where('estado', 'activo')->count();
        $totalProductos = Producto::where('estado', 'activo')->count();
        $productosNormales = $totalProductos - $productosAgotados - $productosStockBajo;

        return [
            Stat::make('Productos Agotados', $productosAgotados)
                ->description('Sin stock disponible')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color('danger')
                ->chart([$productosAgotados])
                ->url(route('filament.admin.resources.productos.productos.index') . '?tableFilters[estado_stock][tipo]=agotado'),
            
            Stat::make('Stock Bajo', $productosStockBajo)
                ->description('Por debajo del mínimo')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->chart([$productosStockBajo])
                ->url(route('filament.admin.resources.productos.productos.index') . '?tableFilters[estado_stock][tipo]=bajo'),
            
            Stat::make('Stock Normal', $productosNormales)
                ->description('Stock suficiente')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->chart([$productosNormales])
                ->url(route('filament.admin.resources.productos.productos.index') . '?tableFilters[estado_stock][tipo]=normal'),
        ];
    }

    public static function canView(): bool
    {
        // Mostrar siempre en el dashboard
        return true;
    }
}
