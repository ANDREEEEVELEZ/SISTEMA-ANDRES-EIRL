<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Información de la venta original
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <strong>Venta:</strong> #{{ $this->venta->id }}
            </div>
            <div>
                <strong>Cliente:</strong> {{ $this->venta->cliente->nombre_razon }}
            </div>
            <div>
                <strong>Total:</strong> S/ {{ number_format($this->venta->total_venta, 2) }}
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            Datos de la nota
        </x-slot>

        {{ $this->form }}
    </x-filament::section>
</x-filament-panels::page>
