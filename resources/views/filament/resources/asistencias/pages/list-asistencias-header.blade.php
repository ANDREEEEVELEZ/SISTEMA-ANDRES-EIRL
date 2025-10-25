{{-- Calendario de Asistencias --}}
<div class="mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
        <div class="p-6">
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
    </div>
</div>
