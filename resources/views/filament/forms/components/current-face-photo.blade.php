@php
    $record = $getRecord();
    $photoPath = $record?->foto_facial_path;
@endphp

<div class="space-y-2">
    @if($photoPath && \Storage::disk('public')->exists($photoPath))
        <div class="flex items-center space-x-4 p-4 bg-success-50 dark:bg-success-950/20 rounded-lg border-2 border-success-500">
            <div class="flex-shrink-0">
                <img 
                    src="{{ asset('storage/' . $photoPath) }}" 
                    alt="Rostro de {{ $record->nombres }}"
                    class="w-32 h-32 rounded-full object-cover border-4 border-success-500 shadow-lg"
                />
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <x-filament::icon 
                        icon="heroicon-o-check-circle" 
                        class="w-5 h-5 text-success-600"
                    />
                    <p class="text-sm font-semibold text-success-700 dark:text-success-500">
                        Rostro registrado correctamente
                    </p>
                </div>
                <div class="space-y-1">
                    <p class="text-xs text-gray-600 dark:text-gray-400">
                        <span class="font-medium">Archivo:</span> {{ basename($photoPath) }}
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">
                        <span class="font-medium">DNI:</span> {{ $record->dni }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-500">
                        El empleado puede marcar asistencia mediante reconocimiento facial
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="flex items-start space-x-3 p-4 bg-warning-50 dark:bg-warning-950/20 rounded-lg border-2 border-warning-400">
            <x-filament::icon 
                icon="heroicon-o-exclamation-triangle" 
                class="w-6 h-6 text-warning-600 flex-shrink-0 mt-0.5"
            />
            <div>
                <p class="text-sm font-medium text-warning-800 dark:text-warning-400 mb-1">
                    No hay rostro registrado para este empleado
                </p>
                <p class="text-xs text-warning-700 dark:text-warning-500">
                    Use el botón "Registrar Rostro" para capturar la imagen facial del empleado
                </p>
            </div>
        </div>
    @endif
</div>
