<?php

namespace App\Filament\Resources\Empleados\Widgets;

use App\Models\Empleado;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EstadisticasEmpleadosWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        return [
            Stat::make('Total de Empleados', Empleado::count())
                ->description('Todos los empleados registrados')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Empleados Inactivos', Empleado::where('estado_empleado', 'inactivo')->count())
                ->description('Empleados con estado inactivo')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color('danger'),

            Stat::make('Empleados Nuevos Este Mes', Empleado::whereBetween('fecha_incorporacion', [$startOfMonth, $endOfMonth])->count())
                ->description('Incorporados en ' . Carbon::now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary'),
        ];
    }
}
