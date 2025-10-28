<x-filament-panels::page>

    {{-- Render Filament form schema (cards, placeholders, inputs) --}}
    <div>
        {{ $this->form }}
    </div>

    <div class="mt-4 flex justify-end space-x-2">
        <button wire:click.prevent="guardar" type="button" @if($this->isConfirmed()) disabled @endif class="fi-btn fi-btn-size-md inline-flex items-center px-3 py-2 rounded bg-yellow-500 text-white hover:bg-yellow-600">Guardar borrador</button>
        <button wire:click.prevent="confirmar" type="button" @if($this->isConfirmed()) disabled @endif class="fi-btn fi-btn-size-md inline-flex items-center px-3 py-2 rounded bg-green-600 text-white hover:bg-green-700">Confirmar Arqueo</button>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
