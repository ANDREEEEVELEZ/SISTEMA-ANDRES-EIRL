<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte de Asistencia - {{ $empleado->nombre_completo }}</title>
    <style>
        body { 
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif; 
            font-size: 11px;
            margin: 20px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
            color: #333;
        }
        .header .info {
            margin: 3px 0;
            font-size: 10px;
            color: #666;
        }
        .section { 
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .section-title {
            background-color: #f0f0f0;
            padding: 6px;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 8px;
            border-left: 4px solid #4a5568;
        }
        table { 
            width: 100%; 
            border-collapse: collapse;
            margin-top: 5px;
        }
        th { 
            background-color: #4a5568;
            color: white;
            padding: 8px 5px;
            font-size: 10px;
            text-align: left;
        }
        td { 
            padding: 6px 5px;
            border-bottom: 1px solid #e0e0e0;
        }
        .right { text-align: right; }
        .center { text-align: center; }
        .big { font-size: 14px; font-weight: bold; }
        
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .stat-item {
            display: table-cell;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            text-align: center;
        }
        .stat-label {
            font-size: 9px;
            color: #666;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 16px;
            font-weight: bold;
            color: #2d3748;
        }
        
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-presente {
            background-color: #c6f6d5;
            color: #22543d;
        }
        .badge-ausente {
            background-color: #fed7d7;
            color: #742a2a;
        }
        .badge-facial {
            background-color: #bee3f8;
            color: #2c5282;
        }
        .badge-manual {
            background-color: #feebc8;
            color: #7c2d12;
        }
        
        .observacion-item {
            padding: 8px;
            margin-bottom: 8px;
            background-color: #fffbeb;
            border-left: 3px solid #f59e0b;
        }
        .observacion-fecha {
            font-weight: bold;
            color: #92400e;
            font-size: 10px;
        }
        .observacion-texto {
            margin-top: 3px;
            font-size: 10px;
            color: #78350f;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>REPORTE DE ASISTENCIA</h2>
        <div class="info">Trabajador: <strong>{{ $empleado->nombre_completo }}</strong></div>
        <div class="info">DNI: {{ $empleado->dni }}</div>
        <div class="info">Período: {{ $fecha_inicio->format('d/m/Y') }} - {{ $fecha_fin->format('d/m/Y') }}</div>
        <div class="info">Generado: {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    @if($incluir_resumen)
    <div class="section">
        <div class="section-title">📊 RESUMEN DEL PERÍODO</div>
        
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-label">Total Días</div>
                <div class="stat-value">{{ $estadisticas['total_dias'] }}</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Días Trabajados</div>
                <div class="stat-value" style="color: #22543d;">{{ $estadisticas['dias_trabajados'] }}</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Ausencias</div>
                <div class="stat-value" style="color: #742a2a;">{{ $estadisticas['ausencias'] }}</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">% Asistencia</div>
                <div class="stat-value" style="color: #2c5282;">{{ number_format($estadisticas['porcentaje_asistencia'], 1) }}%</div>
            </div>
        </div>

        <div class="stats-grid" style="margin-top: 5px;">
            <div class="stat-item">
                <div class="stat-label">Total Horas Trabajadas</div>
                <div class="stat-value">{{ $estadisticas['total_horas'] }}h {{ $estadisticas['total_minutos'] }}m</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Promedio Horas/Día</div>
                <div class="stat-value">{{ $estadisticas['promedio_horas'] }}h {{ $estadisticas['promedio_minutos'] }}m</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Registros Faciales</div>
                <div class="stat-value" style="color: #1e40af;">{{ $estadisticas['registros_faciales'] }}</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Registros Manuales</div>
                <div class="stat-value" style="color: #b45309;">{{ $estadisticas['registros_manuales'] }}</div>
            </div>
        </div>
        
        @if($estadisticas['dias_sin_salida'] > 0)
        <div style="margin-top: 10px; padding: 8px; background-color: #fef3c7; border-left: 3px solid #f59e0b;">
            <span style="font-size: 10px; color: #92400e;">
                ⚠️ <strong>Atención:</strong> Hay {{ $estadisticas['dias_sin_salida'] }} día(s) sin registro de salida
            </span>
        </div>
        @endif
    </div>
    @endif

    @if($incluir_detalle)
    <div class="section">
        <div class="section-title">📅 DÍAS TRABAJADOS</div>
        
        @if($asistencias->where('estado', 'presente')->isEmpty())
            <p style="text-align: center; color: #666; padding: 20px;">
                No hay registros de días trabajados en este período.
            </p>
        @else
            <table>
                <thead>
                    <tr>
                        <th style="width: 15%;">Fecha</th>
                        <th style="width: 10%;">Día</th>
                        <th style="width: 12%;">Entrada</th>
                        <th style="width: 12%;">Salida</th>
                        <th style="width: 12%;" class="right">Horas</th>
                        @if($incluir_metodo)
                        <th style="width: 15%;" class="center">Método</th>
                        @endif
                        @if($incluir_observaciones)
                        <th>Observación</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($asistencias->where('estado', 'presente') as $asistencia)
                        @php
                            $fecha = \Carbon\Carbon::parse($asistencia->fecha);
                            $horasTrabajadas = null;
                            
                            if ($asistencia->hora_entrada && $asistencia->hora_salida) {
                                $entrada = \Carbon\Carbon::parse($asistencia->hora_entrada);
                                $salida = \Carbon\Carbon::parse($asistencia->hora_salida);
                                $minutosTrabajados = $entrada->diffInMinutes($salida);
                                $horas = floor($minutosTrabajados / 60);
                                $minutos = $minutosTrabajados % 60;
                                $horasTrabajadas = $horas . 'h ' . $minutos . 'm';
                            }
                        @endphp
                        
                        <tr>
                            <td>{{ $fecha->format('d/m/Y') }}</td>
                            <td>{{ $fecha->locale('es')->isoFormat('dddd') }}</td>
                            <td>{{ $asistencia->hora_entrada ? \Carbon\Carbon::parse($asistencia->hora_entrada)->format('H:i') : '-' }}</td>
                            <td>{{ $asistencia->hora_salida ? \Carbon\Carbon::parse($asistencia->hora_salida)->format('H:i') : '-' }}</td>
                            <td class="right">{{ $horasTrabajadas ?? 'Sin salida' }}</td>
                            @if($incluir_metodo)
                            <td class="center">
                                @if($asistencia->metodo_registro === 'facial')
                                    <span class="badge badge-facial">Facial</span>
                                @elseif($asistencia->metodo_registro === 'manual_dni')
                                    <span class="badge badge-manual">Manual</span>
                                @else
                                    -
                                @endif
                            </td>
                            @endif
                            @if($incluir_observaciones)
                            <td style="font-size: 9px;">{{ $asistencia->observacion ?? '-' }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    @endif

    {{-- NUEVA SECCIÓN: Incidencias --}}
    @php
        $incidencias = [];
        
        // Detectar días sin salida registrada
        $sinSalida = $asistencias->filter(function($a) {
            return $a->estado === 'presente' && $a->hora_entrada && !$a->hora_salida;
        });
        
        // Detectar días con pocas horas trabajadas (menos de 4 horas)
        $pocasHoras = $asistencias->filter(function($a) {
            if ($a->estado === 'presente' && $a->hora_entrada && $a->hora_salida) {
                $entrada = \Carbon\Carbon::parse($a->hora_entrada);
                $salida = \Carbon\Carbon::parse($a->hora_salida);
                $horas = $entrada->diffInHours($salida);
                return $horas < 4;
            }
            return false;
        });
        
        $hayIncidencias = $sinSalida->count() > 0 || $pocasHoras->count() > 0;
    @endphp

    @if($hayIncidencias)
    <div class="section">
        <div class="section-title">⚠️ INCIDENCIAS DETECTADAS</div>
        
        @if($sinSalida->count() > 0)
        <div style="margin-bottom: 10px;">
            <strong style="color: #c2410c;">🔴 Días sin registro de salida:</strong>
            <div style="margin-left: 15px; margin-top: 5px;">
                @foreach($sinSalida as $dia)
                    <div style="padding: 3px 0; font-size: 10px;">
                        • {{ \Carbon\Carbon::parse($dia->fecha)->format('d/m/Y') }} 
                        - Entrada: {{ \Carbon\Carbon::parse($dia->hora_entrada)->format('H:i') }}
                    </div>
                @endforeach
            </div>
        </div>
        @endif
        
        @if($pocasHoras->count() > 0)
        <div style="margin-bottom: 10px;">
            <strong style="color: #b45309;">🟡 Días con menos de 4 horas trabajadas:</strong>
            <div style="margin-left: 15px; margin-top: 5px;">
                @foreach($pocasHoras as $dia)
                    @php
                        $entrada = \Carbon\Carbon::parse($dia->hora_entrada);
                        $salida = \Carbon\Carbon::parse($dia->hora_salida);
                        $minutos = $entrada->diffInMinutes($salida);
                        $h = floor($minutos / 60);
                        $m = $minutos % 60;
                    @endphp
                    <div style="padding: 3px 0; font-size: 10px;">
                        • {{ \Carbon\Carbon::parse($dia->fecha)->format('d/m/Y') }} 
                        - Solo trabajó {{ $h }}h {{ $m }}m
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    @if($incluir_observaciones && $registros_especiales->count() > 0)
    <div class="section">
        <div class="section-title">📝 REGISTROS MANUALES Y OBSERVACIONES</div>
        
        @foreach($registros_especiales as $registro)
        <div class="observacion-item">
            <div class="observacion-fecha">
                📅 {{ \Carbon\Carbon::parse($registro->fecha)->format('d/m/Y') }}
                @if($registro->metodo_registro === 'manual_dni')
                    - <span style="color: #c2410c;">Registro Manual (DNI)</span>
                @endif
            </div>
            
            @if($registro->razon_manual)
            <div class="observacion-texto">
                <strong>Razón:</strong> {{ $registro->razon_manual }}
            </div>
            @endif
            
            @if($registro->observacion)
            <div class="observacion-texto">
                <strong>Observación:</strong> {{ $registro->observacion }}
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <div class="footer">
        <div>Reporte generado automáticamente por el Sistema de Gestión</div>
        <div>{{ now()->format('d/m/Y H:i:s') }}</div>
    </div>
</body>
</html>
