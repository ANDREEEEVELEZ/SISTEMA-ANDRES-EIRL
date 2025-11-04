<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte General de Asistencias</title>
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
        
        .highlight-row {
            background-color: #f0fdf4;
        }
        
        .low-attendance {
            color: #dc2626;
            font-weight: bold;
        }
        
        .good-attendance {
            color: #16a34a;
            font-weight: bold;
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
        <h2>REPORTE GENERAL DE ASISTENCIAS</h2>
        <div class="info">Período: {{ $fecha_inicio->format('d/m/Y') }} - {{ $fecha_fin->format('d/m/Y') }}</div>
        <div class="info">Generado: {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    @if($incluir_resumen)
    <div class="section">
        <div class="section-title">📊 RESUMEN GENERAL</div>
        
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-label">Total Trabajadores</div>
                <div class="stat-value">{{ $resumen_general['total_trabajadores'] }}</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Promedio Asistencia</div>
                <div class="stat-value" style="color: #2c5282;">{{ number_format($resumen_general['promedio_asistencia'], 1) }}%</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Total Horas Trabajadas</div>
                <div class="stat-value" style="color: #16a34a;">{{ $resumen_general['total_horas'] }}h {{ $resumen_general['total_minutos'] }}m</div>
            </div>
        </div>
    </div>
    @endif

    <div class="section">
        <div class="section-title">👥 DETALLE POR TRABAJADOR</div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 30%;">Trabajador</th>
                    <th style="width: 10%;" class="center">Total Días</th>
                    <th style="width: 12%;" class="center">Trabajados</th>
                    <th style="width: 12%;" class="center">Ausencias</th>
                    <th style="width: 12%;" class="center">% Asistencia</th>
                    <th style="width: 12%;" class="right">Horas Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reporte_general as $item)
                <tr class="{{ $item['porcentaje_asistencia'] >= 95 ? 'highlight-row' : '' }}">
                    <td>{{ $item['empleado']->nombre_completo }}</td>
                    <td class="center">{{ $item['total_dias'] }}</td>
                    <td class="center">{{ $item['dias_trabajados'] }}</td>
                    <td class="center">{{ $item['ausencias'] }}</td>
                    <td class="center">
                        <span class="{{ $item['porcentaje_asistencia'] < 80 ? 'low-attendance' : ($item['porcentaje_asistencia'] >= 95 ? 'good-attendance' : '') }}">
                            {{ number_format($item['porcentaje_asistencia'], 1) }}%
                        </span>
                    </td>
                    <td class="right">{{ $item['total_horas'] }}h {{ $item['total_minutos'] }}m</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #f3f4f6; font-weight: bold;">
                    <td colspan="5" class="right">TOTAL GENERAL:</td>
                    <td class="right">{{ $resumen_general['total_horas'] }}h {{ $resumen_general['total_minutos'] }}m</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="section" style="font-size: 9px; color: #666; margin-top: 15px;">
        <div><strong>Nota:</strong></div>
        <div>• Los días trabajados corresponden a asistencias con estado "Presente"</div>
        <div>• Las ausencias incluyen todos los días sin registro de asistencia</div>
        <div>• Las horas trabajadas se calculan entre hora de entrada y salida registradas</div>
        <div>• Trabajadores con 95% o más de asistencia están resaltados en verde</div>
        <div>• Trabajadores con menos de 80% de asistencia están marcados en rojo</div>
    </div>

    <div class="footer">
        <div>Reporte generado automáticamente por el Sistema de Gestión</div>
        <div>{{ now()->format('d/m/Y H:i:s') }}</div>
    </div>
</body>
</html>
