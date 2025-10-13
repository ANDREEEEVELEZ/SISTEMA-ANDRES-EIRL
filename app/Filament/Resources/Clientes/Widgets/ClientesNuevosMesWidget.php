<?php

namespace App\Filament\Resources\Clientes\Widgets;

use App\Models\Cliente;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClientesNuevosMesWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $clientesNuevosMes = Cliente::whereBetween('fecha_registro', [$startOfMonth, $endOfMonth])->count();

        return [
            Stat::make('Clientes Nuevos Este Mes', $clientesNuevosMes)
                ->description('Registrados en ' . Carbon::now()->format('F Y'))
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary'),
        ];
    }
}
