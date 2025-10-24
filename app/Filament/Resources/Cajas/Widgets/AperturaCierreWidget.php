<?php

namespace App\Filament\Resources\Cajas\Widgets;

use App\Models\Caja;
use App\Services\CajaService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AperturaCierreWidget extends Widget
{
    protected string $view = 'filament.resources.cajas.widgets.apertura-cierre-widget';

      protected int | string | array $columnSpan = 1;


   // protected int | string | array $columnSpan = 'full';

    public $saldoApertura = 0;
    public $saldoCierre = 0;

    public function abrirCaja()
    {
        // Validar
        if (CajaService::tieneCajaAbiertaDiaAnterior()) {
            Notification::make()
                ->title('Error')
                ->body('Debe cerrar la caja del día anterior primero')
                ->danger()
                ->send();
            return;
        }

        if ($this->tieneCajaAbierta()) {
            Notification::make()
                ->title('Error')
                ->body('Ya existe una caja abierta hoy')
                ->warning()
                ->send();
            return;
        }

        // Crear caja
        Caja::create([
            'user_id' => Auth::id(),
            'fecha_apertura' => now(),
            'saldo_inicial' => $this->saldoApertura,
            'estado' => 'abierta',
        ]);

        Notification::make()
            ->title('Caja Abierta')
            ->body('La caja se ha abierto correctamente con S/ ' . number_format($this->saldoApertura, 2))
            ->success()
            ->send();

        $this->saldoApertura = 0;
        $this->dispatch('$refresh');
    }

    public function cerrarCaja()
    {
        $caja = $this->getCajaAbierta();

        if (!$caja) {
            Notification::make()
                ->title('Error')
                ->body('No hay una caja abierta')
                ->danger()
                ->send();
            return;
        }

        $saldoEsperado = $this->calcularSaldoEsperado();
        $diferencia = $this->saldoCierre - $saldoEsperado;

        $caja->update([
            'fecha_cierre' => now(),
            'saldo_final' => $this->saldoCierre,
            'diferencia' => $diferencia,
            'estado' => 'cerrada',
        ]);

        $mensaje = 'La caja se ha cerrado correctamente';
        if ($diferencia != 0) {
            $tipo = $diferencia > 0 ? 'sobrante' : 'faltante';
            $mensaje .= sprintf('. Hay un %s de S/ %.2f', $tipo, abs($diferencia));
        }

        Notification::make()
            ->title('Caja Cerrada')
            ->body($mensaje)
            ->success()
            ->send();

        $this->saldoCierre = 0;
        $this->dispatch('$refresh');
    }

    public function tieneCajaAbierta(): bool
    {
        return Caja::where('estado', 'abierta')
            ->whereDate('fecha_apertura', today())
            ->exists();
    }

    public function getCajaAbierta(): ?Caja
    {
        return Caja::where('estado', 'abierta')
            ->whereDate('fecha_apertura', today())
            ->first();
    }

    public function calcularSaldoEsperado(): float
    {
        $caja = $this->getCajaAbierta();

        if (!$caja) {
            return 0;
        }

        // Saldo con el que se abrió la caja
        $saldoInicial = $caja->saldo_inicial;

        // Ventas SOLO en efectivo que NO estén anuladas
        $ingresosVentasEfectivo = $caja->ventas()
            ->where('metodo_pago', 'efectivo')
            ->where('estado_venta', '!=', 'anulada') // Excluir ventas anuladas
            ->sum('total_venta');

        // Otros ingresos EN EFECTIVO (movimientos_caja solo maneja efectivo)
        $otrosIngresos = $caja->movimientosCaja()
            ->where('tipo', 'ingreso')
            ->sum('monto');

        // Gastos EN EFECTIVO (movimientos_caja solo maneja efectivo)
        $gastos = $caja->movimientosCaja()
            ->where('tipo', 'egreso')
            ->sum('monto');

        // Saldo esperado = lo que debería haber en efectivo físico
        return $saldoInicial + $ingresosVentasEfectivo + $otrosIngresos - $gastos;
    }
}
