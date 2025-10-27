<?php

namespace App\Filament\Widgets;

use App\Models\Categoria;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CategoriasStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Total de categorías
        $totalCategorias = Categoria::count();

        // Categorías activas
        $categoriasActivas = Categoria::where('estado', true)->count();

        // Categorías inactivas
        $categoriasInactivas = Categoria::where('estado', false)->count();

        // Total de productos en todas las categorías
        $totalProductos = Categoria::withCount('productos')->get()->sum('productos_count');

        return [
            Stat::make('Total Categorías', $totalCategorias)
                ->description('Categorías registradas')
                ->descriptionIcon('heroicon-m-tag')
                ->color('info'),

            Stat::make('Categorías Activas', $categoriasActivas)
                ->description('Disponibles para uso')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Categorías Inactivas', $categoriasInactivas)
                ->description('No disponibles')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Total Productos', $totalProductos)
                ->description('En todas las categorías')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
        ];
    }
}
