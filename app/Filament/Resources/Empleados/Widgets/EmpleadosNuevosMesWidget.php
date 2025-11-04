<?php

namespace App\Filament\Resources\Empleados\Widgets;

use App\Models\Empleado;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmpleadosNuevosMesWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $empleadosNuevosMes = Empleado::whereBetween('fecha_incorporacion', [$startOfMonth, $endOfMonth])->count();

        return [
            Stat::make('Empleados Nuevos Este Mes', $empleadosNuevosMes)
                ->description('Incorporados en ' . ucfirst(Carbon::now()->locale('es')->translatedFormat('F Y')))
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary'),
        ];
    }
}
