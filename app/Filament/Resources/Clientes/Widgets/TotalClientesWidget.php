<?php

namespace App\Filament\Resources\Clientes\Widgets;

use App\Models\Cliente;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalClientesWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total de Clientes', Cliente::count())
                ->description('Todos los clientes registrados')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
        ];
    }
}
