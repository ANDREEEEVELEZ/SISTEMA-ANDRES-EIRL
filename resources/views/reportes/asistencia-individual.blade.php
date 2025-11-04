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
        </div>
    </div>
    @endif

    @if($incluir_detalle)
    <div class="section">
        <div class="section-title">📅 DETALLE DIARIO</div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">Fecha</th>
                    <th style="width: 12%;">Entrada</th>
                    <th style="width: 12%;">Salida</th>
                    <th style="width: 12%;" class="center">Estado</th>
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
                @php
                    $fechaActual = $fecha_inicio->copy();
                    $fechaFinalLoop = $fecha_fin->copy();
                @endphp
                
                @while($fechaActual <= $fechaFinalLoop)
                    @php
                        $asistencia = $asistencias->firstWhere('fecha', $fechaActual->format('Y-m-d'));
                        $horasTrabajadas = null;
                        
                        if ($asistencia && $asistencia->hora_entrada && $asistencia->hora_salida) {
                            $entrada = \Carbon\Carbon::parse($asistencia->hora_entrada);
                            $salida = \Carbon\Carbon::parse($asistencia->hora_salida);
                            $minutosTrabajados = $entrada->diffInMinutes($salida);
                            $horas = floor($minutosTrabajados / 60);
                            $minutos = $minutosTrabajados % 60;
                            $horasTrabajadas = $horas . 'h ' . $minutos . 'm';
                        }
                    @endphp
                    
                    <tr>
                        <td>{{ $fechaActual->format('d/m/Y') }}</td>
                        <td>{{ $asistencia && $asistencia->hora_entrada ? \Carbon\Carbon::parse($asistencia->hora_entrada)->format('H:i') : '-' }}</td>
                        <td>{{ $asistencia && $asistencia->hora_salida ? \Carbon\Carbon::parse($asistencia->hora_salida)->format('H:i') : '-' }}</td>
                        <td class="center">
                            @if($asistencia && $asistencia->estado === 'presente')
                                <span class="badge badge-presente">Presente</span>
                            @else
                                <span class="badge badge-ausente">Ausente</span>
                            @endif
                        </td>
                        <td class="right">{{ $horasTrabajadas ?? '-' }}</td>
                        @if($incluir_metodo)
                        <td class="center">
                            @if($asistencia)
                                @if($asistencia->metodo_registro === 'facial')
                                    <span class="badge badge-facial">Facial</span>
                                @elseif($asistencia->metodo_registro === 'manual_dni')
                                    <span class="badge badge-manual">Manual</span>
                                @else
                                    -
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        @endif
                        @if($incluir_observaciones)
                        <td style="font-size: 9px;">{{ $asistencia->observacion ?? '-' }}</td>
                        @endif
                    </tr>
                    
                    @php
                        $fechaActual->addDay();
                    @endphp
                @endwhile
            </tbody>
        </table>
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
