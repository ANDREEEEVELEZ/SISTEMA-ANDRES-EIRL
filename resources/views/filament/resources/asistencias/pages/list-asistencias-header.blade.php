{{-- Calendario de Asistencias --}}
<style>
    @media (min-width: 1280px) {
        .asistencia-container {
            grid-template-columns: 1fr 320px !important;
        }
    }
</style>

<div class="mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
        <div class="p-6">
            {{-- Contenedor principal con Grid responsivo --}}
            <div class="asistencia-container" style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
                {{-- Columna izquierda: Calendario --}}
                <div>
                {{-- Header: Título con Leyenda y Selector de Empleado --}}
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    {{-- Título y Leyenda en la misma línea --}}
                    <div style="display: flex; align-items: center; gap: 2rem;">
                        <h2 style="font-size: 1.125rem; font-weight: 700; color: #111827; margin: 0;">
                            Historial de Asistencia - {{ ucfirst($this->calendarioData['nombreMes']) }}
                        </h2>
                        
                        {{-- Leyenda horizontal junto al título --}}
                        <div style="display: flex; align-items: center; gap: 1.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div style="width: 12px; height: 12px; border-radius: 50%; background-color: #86efac;"></div>
                                <span style="font-size: 0.875rem; color: #374151;">Trabajado</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div style="width: 12px; height: 12px; border-radius: 50%; background-color: #fde047;"></div>
                                <span style="font-size: 0.875rem; color: #374151;">Tardanza</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div style="width: 12px; height: 12px; border-radius: 50%; background-color: #fca5a5;"></div>
                                <span style="font-size: 0.875rem; color: #374151;">Ausencia</span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Selector de Empleado (solo para super_admin) --}}
                    @if(auth()->user()->hasRole('super_admin'))
                        <div style="min-width: 250px;">
                            <select 
                                wire:model.live="empleadoSeleccionado"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option value="">Seleccionar empleado...</option>
                                @foreach($this->empleados as $empleado)
                                    <option value="{{ $empleado->id }}">
                                        {{ $empleado->nombre_completo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

            {{-- Navegación del Mes --}}
            <div class="flex items-center justify-center gap-8 mb-6">
                <button 
                    wire:click="mesAnterior"
                    style="padding: 0.5rem; border-radius: 0.5rem; background-color: transparent; transition: background-color 0.2s;"
                    onmouseover="this.style.backgroundColor='#f3f4f6'"
                    onmouseout="this.style.backgroundColor='transparent'"
                >
                    <span style="font-size: 1.25rem; font-weight: bold; color: #6b7280;">◀</span>
                </button>
                
                <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827; text-transform: capitalize;">
                    {{ $this->calendarioData['nombreMes'] }}
                </h3>
                
                <button 
                    wire:click="mesSiguiente"
                    style="padding: 0.5rem; border-radius: 0.5rem; background-color: transparent; transition: background-color 0.2s;"
                    onmouseover="this.style.backgroundColor='#f3f4f6'"
                    onmouseout="this.style.backgroundColor='transparent'"
                >
                    <span style="font-size: 1.25rem; font-weight: bold; color: #6b7280;">▶</span>
                </button>
            </div>

            {{-- Calendario --}}
            <div class="overflow-x-auto">
                <div style="min-width: 640px;">
                    {{-- Días de la semana --}}
                    <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.75rem; margin-bottom: 1rem;">
                        @foreach(['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'] as $dia)
                            <div style="text-align: center; padding: 0.5rem; font-weight: 600; font-size: 0.875rem; color: #6b7280;">
                                {{ $dia }}
                            </div>
                        @endforeach
                    </div>

                    {{-- Días del mes --}}
                    <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.75rem;">
                        {{-- Días del mes anterior --}}
                        @php
                            $mesAnterior = \Carbon\Carbon::create($this->anioActual, $this->mesActual, 1)->subMonth();
                            $diasMesAnterior = $mesAnterior->daysInMonth;
                            $primerDia = $this->calendarioData['primerDiaSemana'];
                        @endphp
                        
                        @for($i = $primerDia - 1; $i >= 0; $i--)
                            <div 
                                style="aspect-ratio: 2/1; display: flex; align-items: center; justify-content: center; border-radius: 1.5rem; background-color: transparent; color: #d1d5db; font-weight: normal; font-size: 0.95rem; padding: 0.5rem;"
                            >
                                {{ $diasMesAnterior - $i }}
                            </div>
                        @endfor

                        {{-- Días del mes actual --}}
                        @for($dia = 1; $dia <= $this->calendarioData['diasEnMes']; $dia++)
                            @php
                                $asistencia = $this->calendarioData['asistencias'][$dia] ?? null;
                                $estado = $asistencia?->estado;
                                
                                // Determinar color según estado
                                $bgColor = '#f3f4f6';
                                $textColor = '#9ca3af';
                                $fontWeight = 'normal';
                                
                                if ($estado === 'presente') {
                                    $bgColor = '#86efac';
                                    $textColor = '#166534';
                                    $fontWeight = '600';
                                } elseif ($estado === 'tardanza') {
                                    $bgColor = '#fde047';
                                    $textColor = '#854d0e';
                                    $fontWeight = '600';
                                } elseif ($estado === 'ausente') {
                                    $bgColor = '#fca5a5';
                                    $textColor = '#991b1b';
                                    $fontWeight = '600';
                                }

                                // Resaltar día actual
                                $esHoy = $dia == now()->day && $this->mesActual == now()->month && $this->anioActual == now()->year;
                                $border = $esHoy ? 'border: 2px solid #3b82f6;' : '';
                                
                                // Construir título con información de la asistencia
                                $titulo = 'Sin registro';
                                if ($asistencia) {
                                    $titulo = 'Estado: ' . match($estado) {
                                        'presente' => 'Trabajado',
                                        'tardanza' => 'Tardanza',
                                        'ausente' => 'Ausencia',
                                        default => ucfirst($estado)
                                    };
                                    $titulo .= "\nEntrada: " . ($asistencia->hora_entrada ? \Carbon\Carbon::parse($asistencia->hora_entrada)->format('H:i') : '-');
                                    $titulo .= "\nSalida: " . ($asistencia->hora_salida ? \Carbon\Carbon::parse($asistencia->hora_salida)->format('H:i') : '-');
                                }
                            @endphp

                            <div 
                                style="aspect-ratio: 2/1; display: flex; align-items: center; justify-content: center; border-radius: 1.5rem; background-color: {{ $bgColor }}; color: {{ $textColor }}; font-weight: {{ $fontWeight }}; cursor: pointer; transition: all 0.2s; {{ $border }} font-size: 0.95rem; padding: 0.5rem;"
                                title="{{ $titulo }}"
                                onmouseover="this.style.opacity='0.8'"
                                onmouseout="this.style.opacity='1'"
                            >
                                {{ $dia }}
                            </div>
                        @endfor
                        
                        {{-- Días del mes siguiente para completar la cuadrícula --}}
                        @php
                            $totalCeldas = $primerDia + $this->calendarioData['diasEnMes'];
                            $celdasRestantes = $totalCeldas % 7;
                            $diasSiguientes = $celdasRestantes > 0 ? 7 - $celdasRestantes : 0;
                        @endphp
                        
                        @for($dia = 1; $dia <= $diasSiguientes; $dia++)
                            <div 
                                style="aspect-ratio: 2/1; display: flex; align-items: center; justify-content: center; border-radius: 1.5rem; background-color: transparent; color: #d1d5db; font-weight: normal; font-size: 0.95rem; padding: 0.5rem;"
                            >
                                {{ $dia }}
                            </div>
                        @endfor
                    </div>
                </div>
            </div>

            {{-- Mensaje si no hay empleado seleccionado --}}
            @if(!$this->empleadoSeleccionado)
                <div style="margin-top: 1.5rem; padding: 1rem; background-color: #dbeafe; border-radius: 0.5rem; border-left: 4px solid #3b82f6;">
                    <p style="font-size: 0.875rem; color: #1e40af; text-align: center;">
                        @if(auth()->user()->hasRole('super_admin'))
                            Selecciona un empleado para ver su historial de asistencias
                        @else
                            No se encontró un empleado asociado a tu cuenta
                        @endif
                    </p>
                </div>
            @endif
            </div>

            {{-- Columna derecha: Foto del Empleado --}}
            @if($this->empleadoSeleccionadoData)
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: flex-start;">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 1.5rem; padding: 2rem; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 320px;">
                        <h3 style="font-size: 1.125rem; font-weight: 700; color: white; text-align: center; margin-bottom: 1.5rem;">
                            {{ $this->empleadoSeleccionadoData->nombre_completo }}
                        </h3>
                        
                        {{-- Contenedor de la foto --}}
                        <div style="width: 180px; height: 180px; margin: 0 auto 1.5rem; border-radius: 50%; background: white; padding: 8px; box-shadow: 0 8px 20px rgba(0,0,0,0.15);">
                            @if($this->empleadoSeleccionadoData->foto_facial_path)
                                <img 
                                    src="{{ asset('storage/' . $this->empleadoSeleccionadoData->foto_facial_path) }}" 
                                    alt="Foto de {{ $this->empleadoSeleccionadoData->nombre_completo }}"
                                    style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;"
                                    onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%239ca3af%22%3E%3Cpath d=%22M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z%22/%3E%3C/svg%3E'"
                                >
                            @else
                                {{-- Avatar placeholder cuando no hay foto --}}
                                <div style="width: 100%; height: 100%; border-radius: 50%; background: linear-gradient(135deg, #e0e7ff 0%, #cffafe 100%); display: flex; align-items: center; justify-content: center;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#9ca3af" style="width: 60%; height: 60%;">
                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        {{-- Información adicional del empleado --}}
                        <div style="background: rgba(255,255,255,0.15); border-radius: 1rem; padding: 1.25rem; backdrop-filter: blur(10px);">
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" style="width: 20px; height: 20px; flex-shrink: 0;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                                </svg>
                                <div>
                                    <p style="font-size: 0.75rem; color: rgba(255,255,255,0.8); margin: 0;">DNI</p>
                                    <p style="font-size: 0.95rem; font-weight: 600; color: white; margin: 0;">{{ $this->empleadoSeleccionadoData->dni }}</p>
                                </div>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" style="width: 20px; height: 20px; flex-shrink: 0;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                                <div>
                                    <p style="font-size: 0.75rem; color: rgba(255,255,255,0.8); margin: 0;">Mes Actual</p>
                                    <p style="font-size: 0.95rem; font-weight: 600; color: white; margin: 0; text-transform: capitalize;">{{ $this->calendarioData['nombreMes'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            </div>
        </div>
    </div>
</div>
