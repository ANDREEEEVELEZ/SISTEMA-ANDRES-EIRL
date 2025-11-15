<x-filament-panels::page>

    {{-- Render Filament form schema (cards, placeholders, inputs) --}}
    <div>
        {{ $this->form }}
    </div>

    <div class="mt-4 flex justify-end space-x-2">
        <button wire:click.prevent="guardar" type="button" @if($this->isConfirmed()) disabled @endif class="fi-btn fi-btn-size-md inline-flex items-center px-3 py-2 rounded" style="background-color: #2563eb !important; color: white !important;">Guardar borrador</button>
        <button wire:click.prevent="confirmar" type="button" @if($this->isConfirmed()) disabled @endif class="fi-btn fi-btn-size-md inline-flex items-center px-3 py-2 rounded" style="background-color: #2563eb !important; color: white !important;">Confirmar Arqueo</button>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
