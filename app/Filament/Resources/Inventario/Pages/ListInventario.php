<?php

namespace App\Filament\Resources\Inventario\Pages;

use App\Filament\Actions\ExportarInventarioStockAction;
use App\Filament\Actions\ExportarMovimientosInventarioAction;
use App\Filament\Actions\ExportarReporteCompletoAction;
use App\Filament\Resources\Inventario\InventarioResource;
use App\Filament\Resources\Inventario\Widgets\InventarioResumenWidget;
use App\Filament\Resources\Inventario\Widgets\MovimientosStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListInventario extends ListRecords
{
    protected static string $resource = InventarioResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            CreateAction::make(),
        ];

        // Solo super_admin puede ver las opciones de exportación
        if (Auth::user() && Auth::user()->hasRole('super_admin')) {
            array_unshift($actions, 
                ExportarInventarioStockAction::make(),
                ExportarMovimientosInventarioAction::make(),
                ExportarReporteCompletoAction::make()
            );
        }

        return $actions;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InventarioResumenWidget::class,          // Resumen general del inventario
            MovimientosStatsWidget::class,           // Estadísticas de movimientos del mes
        ];
    }
}