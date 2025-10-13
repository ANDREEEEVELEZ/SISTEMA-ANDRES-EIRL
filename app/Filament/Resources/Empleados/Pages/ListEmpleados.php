<?php

namespace App\Filament\Resources\Empleados\Pages;

use App\Filament\Resources\Empleados\EmpleadoResource;
use App\Filament\Resources\Empleados\Widgets\EstadisticasEmpleadosWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmpleados extends ListRecords
{
    protected static string $resource = EmpleadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Registrar Empleado'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EstadisticasEmpleadosWidget::class,
        ];
    }
}
