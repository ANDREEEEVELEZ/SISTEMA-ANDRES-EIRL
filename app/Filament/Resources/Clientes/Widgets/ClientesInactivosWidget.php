<?php

namespace App\Filament\Resources\Clientes\Widgets;

use App\Models\Cliente;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClientesInactivosWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Clientes Inactivos', Cliente::where('estado', 'inactivo')->count())
                ->description('Clientes con estado inactivo')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color('danger'),
        ];
    }
}
