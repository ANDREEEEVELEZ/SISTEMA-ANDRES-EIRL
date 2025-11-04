<?php

namespace App\Filament\Resources\Cajas\Widgets;

use App\Models\Caja;
use App\Services\CajaService;
use App\Filament\Resources\Cajas\CajaResource;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use App\Models\Arqueo;

class AperturaCierreWidget extends Widget
{
    protected string $view = 'filament.resources.cajas.widgets.apertura-cierre-widget';

    protected int | string | array $columnSpan = 'full';

    public $saldoApertura = 0;
    public $saldoCierre = 0;
    public $observacionApertura = '';
    public $observacionCierre = '';
    // Guardamos la id en lugar del modelo para evitar problemas de serialización
    public $ultimaCajaAbiertaId = null;
    // Guardamos el saldo inicial en memoria para mostrarlo inmediatamente tras crear la caja
    public $ultimaCajaAbiertaSaldo = null;

    /**
     * Hook que se ejecuta al cargar el widget.
     * Auto-llena el saldo de cierre con el efectivo contado del arqueo confirmado.
     */
    public function mount(): void
    {
        $this->cargarSaldoCierreDesdeArqueo();
    }

    /**
     * Carga el saldo de cierre desde el efectivo contado del arqueo confirmado.
     */
    public function cargarSaldoCierreDesdeArqueo(): void
    {
        $arqueo = $this->getArqueoConfirmado();

        if ($arqueo && $arqueo->efectivo_contado) {
            $this->saldoCierre = (float) $arqueo->efectivo_contado;
        }
    }

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

        // Crear caja y guardar referencia local para evitar problemas de re-render
        $nuevaCaja = Caja::create([
            'user_id' => Auth::id(),
            'fecha_apertura' => now(),
            'saldo_inicial' => (float) $this->saldoApertura,
            'observacion' => $this->observacionApertura ?: null,
            'estado' => 'abierta',
        ]);

        Notification::make()
            ->title('Caja Abierta')
            ->body('La caja se ha abierto correctamente con S/ ' . number_format($this->saldoApertura, 2))
            ->success()
            ->send();

        // Mantener la id de la caja recién creada para mostrar su saldo inmediatamente
        $this->ultimaCajaAbiertaId = $nuevaCaja->id;
        // Guardar el saldo en memoria para asegurar que la vista muestre el valor correcto
        $this->ultimaCajaAbiertaSaldo = (float) $nuevaCaja->saldo_inicial;
        $this->saldoApertura = 0;
        $this->observacionApertura = '';

        // Redirigir automáticamente a la página de crear ventas
        return redirect()->to(\App\Filament\Resources\Ventas\VentaResource::getUrl('create'));
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

        // Concatenar observaciones: apertura / cierre
        $observacionFinal = $caja->observacion ?: '';
        if ($this->observacionCierre) {
            $observacionFinal = $observacionFinal
                ? $observacionFinal . ' / ' . $this->observacionCierre
                : $this->observacionCierre;
        }

        $caja->update([
            'fecha_cierre' => now(),
            'saldo_final' => $this->saldoCierre,
            'diferencia' => $diferencia,
            'observacion' => $observacionFinal ?: null,
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
        $this->observacionCierre = '';
        // Limpiar referencia local cuando se cierra la caja
        $this->ultimaCajaAbiertaId = null;
        $this->ultimaCajaAbiertaSaldo = null;
    }

    public function tieneCajaAbierta(): bool
    {
        // Usa getCajaAbierta() para respetar la referencia local (ultimaCajaAbierta)
        return (bool) $this->getCajaAbierta();
    }

    public function getCajaAbierta(): ?Caja
    {
        // Si tenemos una caja creada recientemente en esta instancia, cárgala desde BD
        if ($this->ultimaCajaAbiertaId) {
            $caja = Caja::find($this->ultimaCajaAbiertaId);
            if ($caja && $caja->estado === 'abierta') {
                return $caja;
            }
            // si no existe o no está abierta, limpiar referencia
            $this->ultimaCajaAbiertaId = null;
        }

        // Obtiene la última caja abierta (la más reciente) desde la base de datos
        return Caja::where('estado', 'abierta')
            ->orderByDesc('fecha_apertura')
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

    /**
     * Indica si existe un arqueo confirmado para la caja abierta.
     */
    public function arqueoConfirmado(): bool
    {
        $caja = $this->getCajaAbierta();

        if (!$caja) {
            return false;
        }

        return Arqueo::where('caja_id', $caja->id)
            ->where('estado', 'confirmado')
            ->exists();
    }

    /**
     * Obtiene el arqueo confirmado para la caja abierta.
     */
    public function getArqueoConfirmado(): ?Arqueo
    {
        $caja = $this->getCajaAbierta();

        if (!$caja) {
            return null;
        }

        return Arqueo::where('caja_id', $caja->id)
            ->where('estado', 'confirmado')
            ->first();
    }
}
