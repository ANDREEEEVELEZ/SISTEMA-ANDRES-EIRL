<?php

namespace App\Filament\Resources\Clientes\Widgets;

use App\Models\Cliente;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EstadisticasClientesWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        return [
            Stat::make('Total de Clientes', Cliente::count())
                ->description('Todos los clientes registrados')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Clientes Inactivos', Cliente::where('estado', 'inactivo')->count())
                ->description('Clientes con estado inactivo')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color('danger'),

            Stat::make('Clientes Nuevos Este Mes', Cliente::whereBetween('fecha_registro', [$startOfMonth, $endOfMonth])->count())
                ->description('Registrados en ' . Carbon::now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary'),
        ];
    }
}
