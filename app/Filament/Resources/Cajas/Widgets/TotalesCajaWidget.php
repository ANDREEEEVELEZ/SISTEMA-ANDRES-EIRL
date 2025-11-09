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
            $totalVentasEfectivo = 0;
            $totalVentasOtrosMedios = 0;
            $totalIngresos = 0;
            $totalEgresos = 0;
        } else {
            // Excluir ventas anuladas del total y SOLO contar ventas en EFECTIVO
            $totalVentasEfectivo = Venta::where('caja_id', $caja->id)
                ->where('metodo_pago', 'efectivo')
                ->where('estado_venta', '!=', 'anulada')
                ->sum('total_venta');

            // Ventas por OTROS MEDIOS DE PAGO (yape, plin, transferencia, tarjeta)
            $totalVentasOtrosMedios = Venta::where('caja_id', $caja->id)
                ->where('metodo_pago', '!=', 'efectivo')
                ->where('estado_venta', '!=', 'anulada')
                ->sum('total_venta');

            $totalIngresos = MovimientoCaja::where('caja_id', $caja->id)->where('tipo', 'ingreso')->sum('monto');
            $totalEgresos = MovimientoCaja::where('caja_id', $caja->id)->where('tipo', 'egreso')->sum('monto');
        }

        return [
            Stat::make('Total de Ventas (Efectivo)', 'S/ ' . number_format($totalVentasEfectivo, 2))
                ->description('Caja abierta')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Ventas (Otros medios)', 'S/ ' . number_format($totalVentasOtrosMedios, 2))
                ->description('Yape, Plin, Transferencia, Tarjeta')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('info'),

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
