<div class="mb-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
        <!-- Título -->
        <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase">
                Apertura y Cierre de Caja
            </h3>
        </div>

        <!-- Contenido en 2 columnas -->
        <div class="grid grid-cols-2 gap-4 p-4">

            <!-- APERTURA -->
            <div class="space-y-2">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-lg">📂</span>
                    <h4 class="text-sm font-semibold text-green-700 dark:text-green-400">Apertura de Caja</h4>
                </div>

                @if(!$this->tieneCajaAbierta())
                    @if(\App\Services\CajaService::tieneCajaAbiertaDiaAnterior())
                        <div class="p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded text-center">
                            <p class="text-xs text-red-700 dark:text-red-300"> Cierre la caja anterior primero</p>
                        </div>
                    @else
                        <form wire:submit.prevent="abrirCaja" class="space-y-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Saldo Inicial</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-xs text-gray-500">S/</span>
                                    <input
                                        type="number"
                                        wire:model="saldoApertura"
                                        step="0.01"
                                        min="0"
                                        class="pl-7 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-green-500 focus:ring-1 focus:ring-green-500"
                                        placeholder="0.00"
                                        required
                                    />
                                </div>
                            </div>
                            <button
                                type="submit"
                                class="w-full px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded transition"
                            >
                                Abrir Caja
                            </button>
                        </form>
                    @endif
                @else
                    <div class="p-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded text-center">
                        <p class="text-xs font-medium text-green-700 dark:text-green-300">✓ Caja abierta</p>
                        <p class="text-xs text-green-600 dark:text-green-400 mt-0.5">Saldo: S/ {{ number_format($this->getCajaAbierta()?->saldo_inicial ?? 0, 2) }}</p>
                    </div>
                @endif
            </div>

            <!-- CIERRE -->
            <div class="space-y-2 border-l border-gray-200 dark:border-gray-700 pl-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-lg">🔒</span>
                    <h4 class="text-sm font-semibold text-red-700 dark:text-red-400">Cierre de Caja</h4>
                </div>

                @if($this->tieneCajaAbierta())
                    <form wire:submit.prevent="cerrarCaja" class="space-y-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Saldo Final</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-xs text-gray-500">S/</span>
                                <input
                                    type="number"
                                    wire:model="saldoCierre"
                                    step="0.01"
                                    min="0"
                                    class="pl-7 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-red-500 focus:ring-1 focus:ring-red-500"
                                    placeholder="0.00"
                                    required
                                />
                            </div>
                        </div>
                        <div class="p-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded text-center">
                            <p class="text-xs font-medium text-blue-700 dark:text-blue-300">Esperado: S/ {{ number_format($this->calcularSaldoEsperado(), 2) }}</p>
                        </div>
                        <button
                            type="submit"
                            class="w-full px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded transition"
                        >
                            Cerrar Caja
                        </button>
                    </form>
                @else
                    <div class="p-2 bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-center">
                        <p class="text-xs text-gray-600 dark:text-gray-400">No hay caja abierta</p>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
