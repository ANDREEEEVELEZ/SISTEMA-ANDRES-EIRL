<?php

namespace App\Filament\Resources\Cajas\Widgets;

use App\Models\Caja;
use App\Models\Venta;
use App\Models\MovimientoCaja;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalesCajaWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $caja = Caja::where('estado', 'abierta')->latest('fecha_apertura')->first();

        if (! $caja) {
            $totalVentas = 0;
            $totalIngresos = 0;
            $totalEgresos = 0;
        } else {
            $totalVentas = Venta::where('caja_id', $caja->id)->sum('total_venta');
            $totalIngresos = MovimientoCaja::where('caja_id', $caja->id)->where('tipo', 'ingreso')->sum('monto');
            $totalEgresos = MovimientoCaja::where('caja_id', $caja->id)->where('tipo', 'egreso')->sum('monto');
        }

        return [
            Stat::make('Total de Ventas', 'S/ ' . number_format($totalVentas, 2))
                ->description('Caja abierta')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Total de Ingresos', 'S/ ' . number_format($totalIngresos, 2))
                ->description('Caja abierta')
                ->descriptionIcon('heroicon-m-plus')
                ->color('primary'),
            Stat::make('Total de Egresos', 'S/ ' . number_format($totalEgresos, 2))
                ->description('Caja abierta')
                ->descriptionIcon('heroicon-m-minus')
                ->color('danger'),
        ];
    }
}
