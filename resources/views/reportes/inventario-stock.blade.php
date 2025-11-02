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
            font-size: 10pt;
            color: #333;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
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
            width: 50%;
        }

        .info-box strong {
            color: #1e40af;
        }

        .summary-cards {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .summary-card {
            display: table-cell;
            background-color: #f9fafb;
            padding: 10px;
            text-align: center;
            border: 1px solid #e5e7eb;
            width: 25%;
        }

        .summary-card h3 {
            font-size: 20pt;
            color: #1e40af;
            margin-bottom: 5px;
        }

        .summary-card p {
            font-size: 9pt;
            color: #6b7280;
        }

        .summary-card.danger h3 {
            color: #dc2626;
        }

        .summary-card.warning h3 {
            color: #f59e0b;
        }

        .summary-card.success h3 {
            color: #16a34a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background-color: #2563eb;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 9pt;
            font-weight: bold;
        }

        td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9pt;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8pt;
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
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 8pt;
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
        <p><strong>Fecha de generación:</strong> {{ $fecha_generacion }}</p>
        <p><strong>Generado por:</strong> {{ $generado_por }}</p>
    </div>

    <div class="info-box">
        <div class="row">
            <div class="col">
                <strong>Productos totales:</strong> {{ $resumen['total_productos'] }}
            </div>
            <div class="col">
                <strong>Stock total:</strong> {{ number_format($resumen['stock_total'], 0) }} unidades
            </div>
        </div>
    </div>

    <div class="summary-cards">
        <div class="summary-card">
            <h3>{{ $resumen['total_productos'] }}</h3>
            <p>Total Productos</p>
        </div>
        <div class="summary-card success">
            <h3>{{ $resumen['stock_total'] }}</h3>
            <p>Stock Total</p>
        </div>
        <div class="summary-card warning">
            <h3>{{ $resumen['productos_stock_bajo'] }}</h3>
            <p>Stock Bajo</p>
        </div>
        <div class="summary-card danger">
            <h3>{{ $resumen['productos_agotados'] }}</h3>
            <p>Agotados</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 30%">Producto</th>
                <th style="width: 20%">Categoría</th>
                <th style="width: 10%" class="text-center">Stock Actual</th>
                <th style="width: 10%" class="text-center">Stock Mínimo</th>
                <th style="width: 10%" class="text-center">Unidad</th>
                <th style="width: 15%" class="text-center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($productos as $index => $producto)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $producto->nombre_producto }}</td>
                <td>{{ $producto->categoria->NombreCategoria ?? 'Sin categoría' }}</td>
                <td class="text-center"><strong>{{ $producto->stock_total }}</strong></td>
                <td class="text-center">{{ $producto->stock_minimo }}</td>
                <td class="text-center">{{ $producto->unidad_medida }}</td>
                <td class="text-center">
                    @if ($producto->stock_total <= 0)
                        <span class="badge badge-danger">Agotado</span>
                    @elseif ($producto->stock_total <= $producto->stock_minimo)
                        <span class="badge badge-warning">Stock Bajo</span>
                    @else
                        <span class="badge badge-success">Normal</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Este reporte fue generado automáticamente por el sistema de gestión de inventario</p>
        <p>© {{ date('Y') }} - Todos los derechos reservados</p>
    </div>
</body>
</html>
