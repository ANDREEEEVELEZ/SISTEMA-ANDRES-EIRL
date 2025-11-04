@php
    $record = $getRecord();
    $photoPath = $record?->foto_facial_path;
@endphp

{{-- Contenedor principal con Grid --}}
<div style="display: grid; grid-template-columns: auto 1fr; gap: 1.5rem; align-items: start;">
    
    {{-- Tarjeta con la Foto (Izquierda) --}}
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 1.5rem; padding: 2rem; box-shadow: 0 10px 25px rgba(0,0,0,0.15); width: 280px;">
        
        @if($photoPath && \Storage::disk('public')->exists($photoPath))
            {{-- Título --}}
            <h3 style="font-size: 1.125rem; font-weight: 700; color: white; text-align: center; margin-bottom: 1.5rem;">
                Registro Facial
            </h3>
            
            {{-- Contenedor de la foto --}}
            <div style="width: 180px; height: 180px; margin: 0 auto; border-radius: 50%; background: white; padding: 8px; box-shadow: 0 8px 20px rgba(0,0,0,0.15); position: relative;">
                <img 
                    src="{{ asset('storage/' . $photoPath) }}" 
                    alt="Rostro de {{ $record->nombres }}"
                    style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;"
                />
                {{-- Icono de verificación --}}
                <div style="position: absolute; bottom: 8px; right: 8px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 50%; padding: 6px; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.4); border: 3px solid white;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="white" style="width: 16px; height: 16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </div>
            </div>
        @else
            {{-- Título cuando no hay foto --}}
            <h3 style="font-size: 1.125rem; font-weight: 700; color: white; text-align: center; margin-bottom: 1.5rem;">
                Registro Facial
            </h3>
            
            {{-- Placeholder cuando no hay foto --}}
            <div style="width: 180px; height: 180px; margin: 0 auto; border-radius: 50%; background: white; padding: 8px; box-shadow: 0 8px 20px rgba(0,0,0,0.15);">
                <div style="width: 100%; height: 100%; border-radius: 50%; background: linear-gradient(135deg, #e0e7ff 0%, #cffafe 100%); display: flex; align-items: center; justify-content: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#9ca3af" style="width: 60%; height: 60%;">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
            </div>
        @endif
    </div>

    {{-- Botones (Derecha) --}}
    <div style="display: flex; flex-direction: column; gap: 0.75rem; max-width: 280px;">
        {{-- Botón Actualizar/Registrar Rostro --}}
        <button
            type="button"
            onclick="window.dispatchEvent(new CustomEvent('trigger-open-camera'))"
            style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1rem; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border: 2px solid rgba(59, 130, 246, 0.3); border-radius: 0.625rem; font-weight: 600; font-size: 0.875rem; color: white; box-shadow: 0 3px 10px rgba(59, 130, 246, 0.4); cursor: pointer; transition: all 0.3s ease;"
            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 5px 15px rgba(59, 130, 246, 0.5)'"
            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 3px 10px rgba(59, 130, 246, 0.4)'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 18px; height: 18px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
            </svg>
            <span>{{ $photoPath ? 'Actualizar Registro Facial' : 'Registrar Rostro' }}</span>
        </button>
        
        @if($photoPath && \Storage::disk('public')->exists($photoPath))
            {{-- Botón Eliminar Rostro --}}
            <button
                type="button"
                onclick="if(confirm('¿Estás seguro de eliminar el registro facial?')) { window.dispatchEvent(new CustomEvent('trigger-delete-photo')) }"
                style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1rem; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: 2px solid rgba(239, 68, 68, 0.3); border-radius: 0.625rem; font-weight: 600; font-size: 0.875rem; color: white; box-shadow: 0 3px 10px rgba(239, 68, 68, 0.4); cursor: pointer; transition: all 0.3s ease;"
                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 5px 15px rgba(239, 68, 68, 0.5)'"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 3px 10px rgba(239, 68, 68, 0.4)'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 18px; height: 18px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
                <span>Eliminar Rostro</span>
            </button>
        @endif
    </div>

</div>
