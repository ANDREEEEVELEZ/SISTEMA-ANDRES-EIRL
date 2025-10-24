<div class="flex justify-center">
  <div class="grid grid-cols-2 gap-6 max-w-3xl w-full">

    <!-- COLUMNA 1: Botón + TARJETA APERTURA -->
    <div class="flex flex-col space-y-4">

      @if($this->tieneCajaAbierta())
        <!-- BOTÓN con separación real -->
        <div class="flex justify-start pl-6" style="margin-bottom: 2rem;">
          <a href="{{ \App\Filament\Resources\Cajas\CajaResource::getUrl('registrar-movimiento') }}"
             class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-size-md gap-1.5 px-4 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400"
             style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            <span>Registrar Movimiento</span>
          </a>
        </div>
      @endif


    <!-- Sección principal de Apertura y Cierre -->
    <div class="flex justify-center">
        <div class="grid grid-cols-2 gap-6 max-w-3xl w-full" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">

            <!-- TARJETA APERTURA -->

<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding-bottom: 2rem;">
    <div class="fi-section-header flex items-center justify-center gap-x-3 px-4 py-5 border-b border-gray-200 dark:border-white/10">
        <h3 class="fi-section-header-heading text-base font-semibold text-gray-950 dark:text-white"style="padding-left: 3rem;">
            Apertura de Caja
        </h3>
    </div>


                <div class="p-6 flex flex-col items-center space-y-4">
                    @if(\App\Services\CajaService::tieneCajaAbiertaDiaAnterior())
                        <div class="rounded-lg bg-danger-50 p-3 text-sm text-danger-600 dark:bg-danger-400/10 dark:text-danger-400 text-center w-full">
                            ⚠️ Debe cerrar la caja del día anterior primero
                        </div>
                    @else
                        <form wire:submit="abrirCaja" class="space-y-4 w-full max-w-xs" style="opacity: {{ $this->tieneCajaAbierta() ? '0.6' : '1' }}; pointer-events: {{ $this->tieneCajaAbierta() ? 'none' : 'auto' }};">
                            <div style="margin-bottom: 1.5rem;">
                                <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 w-full" style="padding-left: 1rem; justify-content: flex-start;" >
                                    <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white"style="padding-left: 5rem;">
                                        Saldo Inicial
                                    </span>
                                </label>
                                <div style="margin-top: 1rem; display: flex; justify-content: center;">
                                    <div class="fi-input-wrp flex items-center rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20" style="max-width: 200px; width: 100%;">
                                        <div class="flex items-center ps-3 pe-2">
                                            <span class="text-xl font-bold text-gray-1000 dark:text-gray-800">S/</span>
                                        </div>
                                        <input
                                            type="text"
                                            step="0.01"
                                            min="0"
                                            placeholder="0.00"
                                            value="{{ $this->tieneCajaAbierta() ? number_format($this->getCajaAbierta()?->saldo_inicial ?? 0, 2) : '' }}"
                                            {{ $this->tieneCajaAbierta() ? 'disabled readonly' : '' }}
                                            {{ !$this->tieneCajaAbierta() ? 'wire:model=saldoApertura required' : '' }}
                                            class="fi-input block w-full border-none py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] sm:text-sm sm:leading-6 bg-white/0 ps-0"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top: 2rem; padding: 0 1rem; display: flex; justify-content: center;">
                            <button type="submit" {{ $this->tieneCajaAbierta() ? 'disabled' : '' }}
                                class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-success fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm text-white hover:bg-green-600 focus-visible:ring-green-400/50 dark:hover:bg-green-500 fi-ac-action fi-ac-btn-action w-full"
                                style="--c-400:var(--success-400);--c-500:var(--success-500);--c-600:var(--success-600); background-color:#22c55e; color:#ffffff;">
                                {{ $this->tieneCajaAbierta() ? '✓ Caja Abierta' : 'Abrir Caja' }}
                            </button>
                        </div>
                        </form>
                    @endif
                </div>
            </div>

            <!-- TARJETA CIERRE -->
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-header flex items-center justify-center gap-x-3 px-4 py-3 border-b border-gray-200 dark:border-white/10">
                    <h3 class="fi-section-header-heading text-base font-semibold text-gray-950 dark:text-white"style="padding-left: 3rem;">
                        Cierre de Caja
                    </h3>
                </div>

                <div class="p-6 flex flex-col items-center">
                    @if($this->tieneCajaAbierta())
                        <form wire:submit="cerrarCaja" class="space-y-4 w-full max-w-xs">
                            <div style="margin-bottom: 1.5rem;">
                                <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 w-full" style="padding-left: 1rem; justify-content: flex-start;">
                                    <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white"style="padding-left: 5rem;">
                                        Saldo Final
                                    </span>
                                </label>
                                <div style="margin-top: 1rem; display: flex; justify-content: center;">
                                    <div class="fi-input-wrp flex items-center rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20" style="max-width: 200px; width: 100%;">
                                        <div class="flex items-center ps-3 pe-2">
                                            <span class="text-xl font-bold text-gray-1000 dark:text-gray-800">S/</span>
                                        </div>
                                        <input
                                            type="number"
                                            wire:model="saldoCierre"
                                            step="0.01"
                                            min="0"
                                            placeholder="0.00"
                                            required
                                            class="fi-input block w-full border-none py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] dark:text-white dark:placeholder:text-gray-500 sm:text-sm sm:leading-6 bg-white/0 ps-0"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-lg bg-info-50 p-3 dark:bg-info-400/10 text-center" style="padding-left: 3rem;">
                                <div class="text-sm font-medium text-info-600 dark:text-info-400">
                                    Saldo Esperado: S/ {{ number_format($this->calcularSaldoEsperado(), 2) }}
                                </div>
                            </div>
                            <div style="margin-top: 1rem; padding: 0 1rem; display: flex; justify-content: center;">
                                <button type="submit"
                                    class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-danger fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm text-white hover:bg-red-600 focus-visible:ring-red-400/50 dark:hover:bg-red-500 fi-ac-action fi-ac-btn-action w-full"
                                    style="--c-400:var(--danger-400);--c-500:var(--danger-500);--c-600:var(--danger-600); background-color:#ef4444; color:#ffffff;">
                                    Cerrar Caja
                                </button>
                            </div>

                        </form>
                    @else
                        <div class="flex justify-center w-full">
                            <div class="rounded-lg bg-gray-50 p-4 dark:bg-white/5 text-center max-w-xs">
                                <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 w-full" style="padding-left: 1rem; justify-content: flex-start;">
                                    <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white"style="padding-left: 3rem;">
                                        No hay caja abierta
                                    </span>
                                </label>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
