<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.3;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2563eb;
        }

        .header h1 {
            font-size: 16pt;
            color: #1e40af;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 8pt;
            color: #666;
            margin: 2px 0;
        }

        .info-box {
            background-color: #f3f4f6;
            padding: 8px;
            border-radius: 5px;
            margin-bottom: 12px;
            display: table;
            width: 100%;
        }

        .info-box .row {
            display: table-row;
        }

        .info-box .col {
            display: table-cell;
            padding: 3px 10px;
            width: 33.33%;
        }

        .info-box strong {
            color: #1e40af;
        }

        .summary-cards {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .summary-card {
            display: table-cell;
            background-color: #f9fafb;
            padding: 8px;
            text-align: center;
            border: 1px solid #e5e7eb;
        }

        .summary-card h3 {
            font-size: 16pt;
            color: #1e40af;
            margin-bottom: 3px;
        }

        .summary-card p {
            font-size: 8pt;
            color: #6b7280;
        }

        .summary-card.success h3 {
            color: #16a34a;
        }

        .summary-card.danger h3 {
            color: #dc2626;
        }

        .summary-card.warning h3 {
            color: #f59e0b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 8pt;
        }

        th {
            background-color: #2563eb;
            color: white;
            padding: 6px 4px;
            text-align: left;
            font-size: 8pt;
            font-weight: bold;
        }

        td {
            padding: 4px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 8pt;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
            display: inline-block;
        }

        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 7pt;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $titulo }}</h1>
        <p><strong>Periodo:</strong> {{ $periodo['inicio'] }} al {{ $periodo['fin'] }}</p>
        <p><strong>Fecha de generación:</strong> {{ $fecha_generacion }} | <strong>Generado por:</strong> {{ $generado_por }}</p>
    </div>

    <div class="summary-cards">
        <div class="summary-card">
            <h3>{{ $resumen['total_movimientos'] }}</h3>
            <p>Total Movimientos</p>
        </div>
        <div class="summary-card success">
            <h3>{{ $resumen['total_entradas'] }}</h3>
            <p>Total Entradas ({{ $resumen['cantidad_entradas'] }})</p>
        </div>
        <div class="summary-card danger">
            <h3>{{ $resumen['total_salidas'] }}</h3>
            <p>Total Salidas ({{ $resumen['cantidad_salidas'] }})</p>
        </div>
        <div class="summary-card warning">
            <h3>{{ $resumen['cantidad_ajustes'] }}</h3>
            <p>Ajustes</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 8%">Fecha</th>
                <th style="width: 20%">Producto</th>
                <th style="width: 8%" class="text-center">Tipo</th>
                <th style="width: 8%" class="text-center">Cantidad</th>
                <th style="width: 10%" class="text-center">Método</th>
                <th style="width: 10%" class="text-center">Motivo Ajuste</th>
                <th style="width: 26%">Motivo</th>
                <th style="width: 10%">Usuario</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($movimientos as $movimiento)
            <tr>
                <td>{{ \Carbon\Carbon::parse($movimiento->fecha_movimiento)->format('d/m/Y') }}</td>
                <td>{{ $movimiento->producto->nombre_producto ?? 'N/A' }}</td>
                <td class="text-center">
                    @if ($movimiento->tipo === 'entrada')
                        <span class="badge badge-success">Entrada</span>
                    @elseif ($movimiento->tipo === 'salida')
                        <span class="badge badge-danger">Salida</span>
                    @else
                        <span class="badge badge-warning">Ajuste</span>
                    @endif
                </td>
                <td class="text-center">
                    <strong>
                        @if ($movimiento->tipo === 'ajuste' && $movimiento->metodo_ajuste === 'relativo')
                            {{ $movimiento->cantidad_movimiento > 0 ? '+' : '' }}{{ $movimiento->cantidad_movimiento }}
                        @else
                            {{ $movimiento->cantidad_movimiento }}
                        @endif
                    </strong>
                </td>
                <td class="text-center">
                    @if ($movimiento->metodo_ajuste)
                        <span class="badge badge-info">
                            {{ $movimiento->metodo_ajuste === 'absoluto' ? 'Absoluto' : 'Relativo' }}
                        </span>
                    @else
                        -
                    @endif
                </td>
                <td class="text-center">
                    @if ($movimiento->motivo_ajuste)
                        @php
                            $motivoTexto = match($movimiento->motivo_ajuste) {
                                'conteo_fisico' => 'Conteo',
                                'vencido' => 'Vencido',
                                'danado' => 'Dañado',
                                'robo' => 'Robo',
                                'otro' => 'Otro',
                                default => '-'
                            };
                        @endphp
                        {{ $motivoTexto }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ Str::limit($movimiento->motivo_movimiento, 40) }}</td>
                <td>{{ $movimiento->user->name ?? 'N/A' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">No hay movimientos en el periodo seleccionado</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Este reporte fue generado automáticamente por el sistema de gestión de inventario</p>
        <p>© {{ date('Y') }} - Todos los derechos reservados</p>
    </div>
</body>
</html>
