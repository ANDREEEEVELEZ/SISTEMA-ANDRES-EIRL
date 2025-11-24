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
    // Guardamos la id en lugar del modelo para evitar problemas de serialización
    public $ultimaCajaAbiertaId = null;
    // Guardamos el saldo inicial en memoria para mostrarlo inmediatamente tras crear la caja
    public $ultimaCajaAbiertaSaldo = null;
    // Para super_admin: id de caja seleccionada en la UI
    public $selectedCajaId = null;
    // Lista de cajas abiertas (solo para super_admin)
    public $openCajas = [];

    /**
     * Hook que se ejecuta al cargar el widget.
     * Auto-llena el saldo de cierre con el saldo esperado calculado.
     */
    public function mount(): void
    {
        $this->cargarSaldoCierreDesdeArqueo();

        // Si es super_admin, cargar lista de cajas abiertas y selección previa
        $esSuperAdmin = \Illuminate\Support\Facades\Auth::check() && optional(\Illuminate\Support\Facades\Auth::user())->hasRole('super_admin');
        if ($esSuperAdmin) {
            $this->openCajas = Caja::where('estado', 'abierta')->orderByDesc('fecha_apertura')->get()->map(fn($c) => [
                'id' => $c->id,
                'label' => sprintf('Caja #%d — %s — %s', $c->numero_secuencial, $c->user?->name, $c->fecha_apertura?->format('d/m/Y H:i')),
            ])->toArray();
            // Prioridad de selección para super_admin:
            // 1) selección en sesión válida
            // 2) su propia caja abierta (si la tiene)
            // 3) la primera caja abierta global
            $this->selectedCajaId = null;

            $sessionSelected = session('admin_selected_caja_id');
            if ($sessionSelected) {
                $c = Caja::find($sessionSelected);
                if ($c && $c->estado === 'abierta') {
                    $this->selectedCajaId = $sessionSelected;
                } else {
                    session()->forget('admin_selected_caja_id');
                }
            }

            // Si no hay selección en sesión, preferir la caja propia del super_admin
            if (! $this->selectedCajaId) {
                $propia = Caja::where('estado', 'abierta')
                    ->where('user_id', \Illuminate\Support\Facades\Auth::id())
                    ->orderByDesc('fecha_apertura')
                    ->first();
                if ($propia) {
                    $this->selectedCajaId = $propia->id;
                }
            }

            // Si aún no hay selección, usar la primera caja abierta global
            if (! $this->selectedCajaId) {
                $this->selectedCajaId = $this->openCajas[0]['id'] ?? null;
            }

            if ($this->selectedCajaId) {
                $this->ultimaCajaAbiertaId = $this->selectedCajaId;
                $c = Caja::find($this->selectedCajaId);
                $this->ultimaCajaAbiertaSaldo = $c?->saldo_inicial;
            }
        }
    }

    /**
     * Carga el saldo de cierre desde el saldo esperado calculado.
     */
    public function cargarSaldoCierreDesdeArqueo(): void
    {
        // Si hay un arqueo confirmado, usar su efectivo contado
        $arqueo = $this->getArqueoConfirmado();

        if ($arqueo && $arqueo->efectivo_contado) {
            $this->saldoCierre = (float) $arqueo->efectivo_contado;
        } else {
            // Si no hay arqueo confirmado, auto-llenar con el saldo esperado
            $this->saldoCierre = $this->calcularSaldoEsperado();
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

        // Nota: La observación del cierre ya se tomó del arqueo al confirmar
        // Solo mantenemos la observación de apertura si existe
        $observacionFinal = $caja->observacion ?: null;

        $caja->update([
            'fecha_cierre' => now(),
            'saldo_final' => $this->saldoCierre,
            'diferencia' => $diferencia,
            'observacion' => $observacionFinal,
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
        // Si es super_admin y existe una selección en sesión, respetarla
        $esSuperAdmin = \Illuminate\Support\Facades\Auth::check() && optional(\Illuminate\Support\Facades\Auth::user())->hasRole('super_admin');
        if ($esSuperAdmin) {
            $sessionSelected = session('admin_selected_caja_id');
            if ($sessionSelected) {
                $caja = Caja::find($sessionSelected);
                if ($caja && $caja->estado === 'abierta') {
                    return $caja;
                } else {
                    session()->forget('admin_selected_caja_id');
                }
            }

            // Preferir la caja propia del super_admin si la tiene abierta
            $propia = Caja::where('estado', 'abierta')
                ->where('user_id', \Illuminate\Support\Facades\Auth::id())
                ->orderByDesc('fecha_apertura')
                ->first();
            if ($propia) {
                return $propia;
            }
        }

        // Obtiene la última caja abierta (la más reciente) desde la base de datos
        // Si el usuario NO es super_admin, limitar a sus propias cajas
        $esSuperAdmin = \Illuminate\Support\Facades\Auth::check() && optional(\Illuminate\Support\Facades\Auth::user())->hasRole('super_admin');

        $query = Caja::where('estado', 'abierta')->orderByDesc('fecha_apertura');
        if (! $esSuperAdmin) {
            $query->where('user_id', \Illuminate\Support\Facades\Auth::id());
        }

        return $query->first();
    }

    // Acción Livewire para que super_admin cambie la caja seleccionada
    public function updatedSelectedCajaId($value): void
    {
        if (! $value) return;
        session(['admin_selected_caja_id' => $value]);
        $this->ultimaCajaAbiertaId = $value;
        $c = Caja::find($value);
        $this->ultimaCajaAbiertaSaldo = $c?->saldo_inicial;
        $this->emitSelf('refreshWidgets');
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
