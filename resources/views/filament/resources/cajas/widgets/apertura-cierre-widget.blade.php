<div class="flex justify-center">
    <div style="display: flex; flex-direction: row; gap: 2rem; align-items: flex-start; justify-content: center; width: 100%;">

<!-- TARJETA APERTURA -->
<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="width: 48%; min-width: 280px;">
    <div class="fi-section-header flex justify-center py-2 border-b border-gray-200 dark:border-white/10" style="padding-left: 1rem; padding-right: 1rem;">
        <h3 class="fi-section-header-heading text-base font-semibold text-gray-950 dark:text-white text-center">
            Apertura de Caja
        </h3>
    </div>

    <div class="flex flex-col items-center text-center" style="padding: 0.25rem 1.5rem 1rem 1.5rem;">
        @if(\App\Services\CajaService::tieneCajaAbiertaDiaAnterior())
          <div class="rounded-lg bg-danger-50 text-sm text-danger-600 dark:bg-danger-400/10 dark:text-danger-400 text-center w-full"
              style="margin-top: 0rem; padding: 0.25rem;">
                Debe cerrar la caja del día anterior primero
            </div>
        @else
            <form wire:submit="abrirCaja" class="w-full flex flex-col items-center" style="opacity: {{ $this->tieneCajaAbierta() ? '0.6' : '1' }}; pointer-events: {{ $this->tieneCajaAbierta() ? 'none' : 'auto' }};">
                <div class="w-full flex flex-col items-center">
                    <div class="flex justify-center">
                        <div class="fi-input-wrp rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20" style="width: 200px; display: flex; flex-direction: column; align-items: center; padding-top: 0.5rem; padding-bottom: 0.5rem;">
                            <div class="w-full text-center mb-1">
                                <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                    Saldo Inicial
                                </span>
                            </div>

                            <div style="display:flex; align-items: center; width:100%;">
                                <div style="padding-left: 0.75rem; padding-right: 0.5rem; display: flex; align-items: center; min-width: 45px;">
                                    <span class="text-base font-semibold text-gray-600 dark:text-gray-400">S/</span>
                                </div>

                                @if($this->tieneCajaAbierta())
                                    <input type="number" step="0.01" min="0" placeholder="0.00"
                                        value="{{ number_format($this->ultimaCajaAbiertaSaldo ?? $this->getCajaAbierta()?->saldo_inicial ?? 0, 2) }}"
                                        disabled readonly
                                        class="fi-input block w-full border-none py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] sm:text-sm sm:leading-6 bg-white/0"
                                        style="padding-left: 0; text-align: center;" />
                                @else
                                    <input type="number" wire:model="saldoApertura" step="0.01" min="0" placeholder="0.00" required
                                        class="fi-input block w-full border-none py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] dark:text-white dark:placeholder:text-gray-500 sm:text-sm sm:leading-6 bg-white/0"
                                        style="padding-left: 0; text-align: center;" />
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Campo Observación Apertura -->
                    <div class="flex justify-center" style="margin-top: 1rem;">
                        <div class="w-full" style="max-width: 220px;">
                            @if($this->tieneCajaAbierta())
                                <div class="fi-input-wrp rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20" style="padding: 0.5rem;">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Observación:</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300" style="min-height: 2rem; white-space: pre-wrap;">{{ $this->getCajaAbierta()?->observacion ?: 'Sin observación' }}</div>
                                </div>
                            @else
                                <textarea
                                    wire:model="observacionApertura"
                                    rows="2"
                                    placeholder="Observación (opcional)"
                                    class="fi-input block w-full rounded-lg shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 sm:text-xs ring-1 ring-gray-950/10 dark:ring-white/20"
                                    style="resize: none; padding: 0.5rem; border: 1px solid rgb(209 213 219);"
                                ></textarea>
                            @endif
                        </div>
                    </div>

                    <div class="flex justify-center" style="margin-top: 1.5rem;">
                        <button type="submit" {{ $this->tieneCajaAbierta() ? 'disabled' : '' }}
                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-success fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm text-white hover:bg-green-600 focus-visible:ring-green-400/50 dark:hover:bg-green-500 fi-ac-action fi-ac-btn-action"
                            style="--c-400:var(--success-400);--c-500:var(--success-500);--c-600:var(--success-600); background-color:#22c55e; color:#ffffff; width: 220px;">
                            {{ $this->tieneCajaAbierta() ? '✓ Caja Abierta' : 'Abrir Caja' }}
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>

<!-- TARJETA CIERRE -->
<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="width: 48%; min-width: 280px;">
    <div class="fi-section-header flex items-center justify-center gap-x-3 py-4 border-b border-gray-200 dark:border-white/10" style="padding-left: 1.5rem; padding-right: 1.5rem;">
        <h3 class="fi-section-header-heading text-base font-semibold text-gray-950 dark:text-white text-center">
            Cierre de Caja
        </h3>
    </div>

    <div class="flex flex-col items-center text-center" style="padding: 0.75rem 1.5rem 1rem 1.5rem;">
        @if($this->tieneCajaAbierta())
            <form wire:submit="cerrarCaja" class="w-full flex flex-col items-center" style="gap: 1rem;">
                <div class="w-full flex flex-col items-center">
                    <!-- Input Saldo Final -->
                    <div class="flex justify-center" style="margin-bottom: 1rem;">
                        <div class="fi-input-wrp rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20" style="width: 200px; display: flex; flex-direction: column; align-items: center; padding-top: 0.5rem; padding-bottom: 0.5rem;">
                            <div class="w-full text-center mb-1">
                                <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                    Saldo Final
                                </span>
                            </div>

                            <div style="display:flex; align-items: center; width:100%;">
                                <div style="padding-left: 0.75rem; padding-right: 0.5rem; display: flex; align-items: center; min-width: 45px;">
                                    <span class="text-base font-semibold text-gray-600 dark:text-gray-400">S/</span>
                                </div>

                                <input type="number" wire:model="saldoCierre" step="0.01" min="0" placeholder="0.00" required
                                    class="fi-input block w-full border-none py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] dark:text-white dark:placeholder:text-gray-500 sm:text-sm sm:leading-6 bg-white/0"
                                    style="padding-left: 0; text-align: center;" />
                            </div>
                        </div>
                    </div>

                    <!-- Saldo Esperado -->
                    <div class="flex justify-center" style="margin-bottom: 1.25rem;">
                        <div class="rounded-lg bg-info-50 p-2 dark:bg-info-400/10 text-center" style="width: 200px;">
                            <div class="text-sm font-medium text-info-600 dark:text-info-400">
                                Saldo Esperado: S/ {{ number_format($this->calcularSaldoEsperado(), 2) }}
                            </div>
                        </div>
                    </div>

                    <!-- Campo Observación Cierre -->
                    <div class="flex justify-center" style="margin-top: 1rem;">
                        <div class="w-full" style="max-width: 220px;">
                            <textarea
                                wire:model="observacionCierre"
                                rows="2"
                                placeholder="Observación cierre (opcional)"
                                class="fi-input block w-full rounded-lg shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 sm:text-xs ring-1 ring-gray-950/10 dark:ring-white/20"
                                style="resize: none; padding: 0.5rem; border: 1px solid rgb(209 213 219);"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Botón Cerrar Caja -->
                    <div class="flex justify-center" style="margin-bottom: 0.75rem; margin-top: 1rem;">
                        @php
                            $habilitado = $this->arqueoConfirmado();
                        @endphp
                        <button type="submit" {{ $habilitado ? '' : 'disabled' }}
                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-danger fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm text-white hover:bg-red-600 focus-visible:ring-red-400/50 dark:hover:bg-red-500 fi-ac-action fi-ac-btn-action"
                            style="--c-400:var(--danger-400);--c-500:var(--danger-500);--c-600:var(--danger-600); background-color:#ef4444; color:#ffffff; width: 220px;">
                            Cerrar Caja
                        </button>
                    </div>

                    <!-- Mensaje de advertencia -->
                    @unless($this->arqueoConfirmado())
                        <div class="flex flex-col items-center" style="gap: 1rem;">
                            <div class="text-xs text-center text-yellow-700 dark:text-yellow-300" style="width: 200px; line-height: 1.4;">
                                Generar reporte de arqueo antes de cerrar caja
                            </div>
                            <a href="{{ \App\Filament\Resources\Cajas\CajaResource::getUrl('arqueo-caja') }}"
                               class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg gap-1.5 px-3 py-1.5 text-xs inline-grid shadow-sm bg-primary-600 text-white hover:bg-primary-700 focus-visible:ring-primary-500/50"
                               style="width: 200px; margin-top: 1rem;">
                                📊 Generar Arqueo
                            </a>
                        </div>
                    @endunless
                </div>
            </form>
        @else
            <div class="flex justify-center">
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5 text-center" style="width: 200px;">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">
                        No hay caja abierta
                    </span>
                </div>
            </div>
        @endif
    </div>
</div>

    </div>
</div>
