<?php

namespace App\Filament\Resources\Empleados\Widgets;

use App\Models\Empleado;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalEmpleadosWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total de Empleados', Empleado::count())
                ->description('Todos los empleados registrados')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
        ];
    }
}
