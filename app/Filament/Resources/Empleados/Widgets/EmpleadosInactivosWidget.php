<?php

namespace App\Filament\Resources\Empleados\Widgets;

use App\Models\Empleado;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmpleadosInactivosWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Empleados Inactivos', Empleado::where('estado_empleado', 'inactivo')->count())
                ->description('Empleados con estado inactivo')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color('danger'),
        ];
    }
}
