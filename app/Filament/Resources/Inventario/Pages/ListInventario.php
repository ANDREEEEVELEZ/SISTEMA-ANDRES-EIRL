<?php

namespace App\Filament\Resources\Inventario\Pages;

use App\Filament\Resources\Inventario\InventarioResource;
use App\Filament\Resources\Inventario\Widgets\InventarioResumenWidget;
use App\Filament\Resources\Inventario\Widgets\MovimientosStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInventario extends ListRecords
{
    protected static string $resource = InventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InventarioResumenWidget::class,          // Resumen general del inventario
            MovimientosStatsWidget::class,           // Estadísticas de movimientos del mes
        ];
    }
}