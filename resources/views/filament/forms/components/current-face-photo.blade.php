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
                    <svg class="w-5 h-5 text-success-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
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
            <svg class="w-6 h-6 text-warning-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
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
