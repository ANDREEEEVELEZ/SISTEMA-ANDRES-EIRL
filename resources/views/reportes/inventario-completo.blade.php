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
            font-size: 18pt;
            color: #1e40af;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 9pt;
            color: #666;
            margin: 2px 0;
        }

        .section-title {
            background-color: #1e40af;
            color: white;
            padding: 8px;
            margin: 20px 0 10px 0;
            font-size: 12pt;
            font-weight: bold;
        }

        .info-box {
            background-color: #f3f4f6;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            display: table;
            width: 100%;
        }

        .info-box .row {
            display: table-row;
        }

        .info-box .col {
            display: table-cell;
            padding: 3px 10px;
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
            padding: 10px;
            text-align: center;
            border: 1px solid #e5e7eb;
        }

        .summary-card h3 {
            font-size: 16pt;
            margin-bottom: 5px;
        }

        .summary-card p {
            font-size: 8pt;
            color: #6b7280;
        }

        .summary-card.primary h3 {
            color: #1e40af;
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

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $titulo }}</h1>
        <p><strong>Periodo:</strong> {{ $periodo['inicio'] }} al {{ $periodo['fin'] }}</p>
        <p><strong>Fecha de generación:</strong> {{ $fecha_generacion }} | <strong>Generado por:</strong> {{ $generado_por }}</p>
    </div>

    {{-- RESUMEN DE INVENTARIO --}}
    <div class="section-title">📊 Resumen de Inventario</div>

    <div class="summary-cards">
        <div class="summary-card primary">
            <h3>{{ $resumen_stock['total_productos'] }}</h3>
            <p>Total Productos</p>
        </div>
        <div class="summary-card success">
            <h3>{{ $resumen_stock['stock_total'] }}</h3>
            <p>Stock Total</p>
        </div>
        <div class="summary-card warning">
            <h3>{{ $resumen_stock['productos_stock_bajo'] }}</h3>
            <p>Stock Bajo</p>
        </div>
        <div class="summary-card danger">
            <h3>{{ $resumen_stock['productos_agotados'] }}</h3>
            <p>Agotados</p>
        </div>
    </div>

    {{-- PRODUCTOS CON ALERTAS --}}
    <div class="section-title">⚠️ Productos con Alertas</div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 35%">Producto</th>
                <th style="width: 20%">Categoría</th>
                <th style="width: 15%" class="text-center">Stock Actual</th>
                <th style="width: 15%" class="text-center">Stock Mínimo</th>
                <th style="width: 10%" class="text-center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @php
                $productosConAlerta = $productos->filter(fn($p) => $p->stock_total <= $p->stock_minimo);
            @endphp
            @forelse ($productosConAlerta->take(20) as $index => $producto)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $producto->nombre_producto }}</td>
                <td>{{ $producto->categoria->NombreCategoria ?? 'Sin categoría' }}</td>
                <td class="text-center"><strong>{{ $producto->stock_total }}</strong></td>
                <td class="text-center">{{ $producto->stock_minimo }}</td>
                <td class="text-center">
                    @if ($producto->stock_total <= 0)
                        <span class="badge badge-danger">Agotado</span>
                    @else
                        <span class="badge badge-warning">Bajo</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">✅ No hay productos con alertas de stock</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- RESUMEN DE MOVIMIENTOS --}}
    <div class="section-title" style="margin-top: 30px;">📦 Resumen de Movimientos (Periodo: {{ $periodo['inicio'] }} - {{ $periodo['fin'] }})</div>

    <div class="summary-cards">
        <div class="summary-card primary">
            <h3>{{ $resumen_movimientos['total_movimientos'] }}</h3>
            <p>Total Movimientos</p>
        </div>
        <div class="summary-card success">
            <h3>{{ $resumen_movimientos['total_entradas'] }}</h3>
            <p>Entradas ({{ $resumen_movimientos['cantidad_entradas'] }})</p>
        </div>
        <div class="summary-card danger">
            <h3>{{ $resumen_movimientos['total_salidas'] }}</h3>
            <p>Salidas ({{ $resumen_movimientos['cantidad_salidas'] }})</p>
        </div>
        <div class="summary-card warning">
            <h3>{{ $resumen_movimientos['cantidad_ajustes'] }}</h3>
            <p>Ajustes</p>
        </div>
    </div>

    {{-- ÚLTIMOS MOVIMIENTOS --}}
    <div class="section-title">🔄 Últimos Movimientos (Top 50)</div>

    <table>
        <thead>
            <tr>
                <th style="width: 10%">Fecha</th>
                <th style="width: 25%">Producto</th>
                <th style="width: 10%" class="text-center">Tipo</th>
                <th style="width: 10%" class="text-center">Cantidad</th>
                <th style="width: 35%">Motivo</th>
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
                <td>{{ Str::limit($movimiento->motivo_movimiento, 50) }}</td>
                <td>{{ Str::limit($movimiento->user->name ?? 'N/A', 15) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">No hay movimientos en el periodo seleccionado</td>
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
