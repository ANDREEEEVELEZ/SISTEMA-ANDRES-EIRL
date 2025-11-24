<?php

namespace App\Filament\Resources\Cajas\Widgets;

use App\Models\Caja;
use App\Models\Venta;
use App\Models\MovimientoCaja;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class TotalesCajaWidget extends BaseWidget
{
    protected function getStats(): array
    {

        $esSuperAdmin = Auth::check() && optional(Auth::user())->hasRole('super_admin');

        // Si super_admin seleccionó una caja en la sesión, respetarla
        $selected = null;
        if ($esSuperAdmin) {
            $selected = session('admin_selected_caja_id');
        }

        $caja = null;
        if ($selected) {
            $caja = Caja::find($selected);
            // Si la caja seleccionada ya no existe o no está abierta, limpiar la selección
            if (! $caja || $caja->estado !== 'abierta') {
                session()->forget('admin_selected_caja_id');
                $caja = null;
            }
        }

        // Si no hay selección válida y es super_admin, preferir su propia caja abierta
        if (! $caja && $esSuperAdmin) {
            $caja = Caja::where('estado', 'abierta')
                ->where('user_id', Auth::id())
                ->orderByDesc('fecha_apertura')
                ->first();
        }

        // Fallback: última caja abierta global
        if (! $caja) {
            $caja = Caja::where('estado', 'abierta')->orderBy('fecha_apertura', 'desc')->first();
        }

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
