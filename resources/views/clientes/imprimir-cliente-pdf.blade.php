<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Información de Cliente</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }

        .header p {
            margin: 5px 0 0 0;
            font-size: 11px;
            color: #666;
        }

        .section {
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            background: #f0f0f0;
            padding: 8px 10px;
            margin-bottom: 15px;
            border-left: 4px solid #4CAF50;
        }

        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            width: 35%;
            padding: 8px 10px;
            font-weight: bold;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }

        .info-value {
            display: table-cell;
            width: 65%;
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-left: none;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }

        .status-activo {
            background: #4CAF50;
            color: white;
        }

        .status-inactivo {
            background: #f44336;
            color: white;
        }

        .ventas-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .ventas-table th,
        .ventas-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 11px;
        }

        .ventas-table th {
            background: #f0f0f0;
            font-weight: bold;
        }

        .ventas-table tr:nth-child(even) {
            background: #f9f9f9;
        }

        .footer {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #999;
            font-style: italic;
        }

        @page {
            margin: 100px 25px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>INFORMACIÓN DE CLIENTE</h1>
        <p>Fecha de impresión: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="section">
        <div class="section-title">Datos Personales / Empresa</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Tipo de Documento:</div>
                <div class="info-value">{{ strtoupper($cliente->tipo_doc) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">N° de Documento:</div>
                <div class="info-value">{{ $cliente->num_doc }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Nombre / Razón Social:</div>
                <div class="info-value">{{ $cliente->nombre_razon }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Tipo de Cliente:</div>
                <div class="info-value">
                    @switch($cliente->tipo_cliente)
                        @case('natural') Persona Natural @break
                        @case('natural_con_negocio') Natural con Negocio @break
                        @case('juridica') Persona Jurídica @break
                        @default {{ ucfirst($cliente->tipo_cliente) }}
                    @endswitch
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Estado:</div>
                <div class="info-value">
                    <span class="status-badge status-{{ $cliente->estado }}">
                        {{ strtoupper($cliente->estado) }}
                    </span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha de Registro:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($cliente->fecha_registro)->format('d/m/Y') }}</div>
            </div>
        </div>
    </div>

    @if($cliente->direccion || $cliente->telefono || $cliente->email)
    <div class="section">
        <div class="section-title">Información de Contacto</div>
        <div class="info-grid">
            @if($cliente->direccion)
            <div class="info-row">
                <div class="info-label">Dirección:</div>
                <div class="info-value">{{ $cliente->direccion }}</div>
            </div>
            @endif
            @if($cliente->telefono)
            <div class="info-row">
                <div class="info-label">Teléfono:</div>
                <div class="info-value">{{ $cliente->telefono }}</div>
            </div>
            @endif
            @if($cliente->email)
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ $cliente->email }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="section">
        <div class="section-title">Historial de Ventas</div>
        @if($cliente->ventas && $cliente->ventas->count() > 0)
        <table class="ventas-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Comprobante</th>
                    <th>Total</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cliente->ventas as $venta)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y') }}</td>
                    <td>
                        @if($venta->comprobantes->first())
                            {{ $venta->comprobantes->first()->serie }}-{{ $venta->comprobantes->first()->numero }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>S/ {{ number_format($venta->total, 2) }}</td>
                    <td>{{ ucfirst($venta->estado) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="text-align: right; font-weight: bold;">Total Comprado:</td>
                    <td colspan="2" style="font-weight: bold;">S/ {{ number_format($cliente->ventas->sum('total'), 2) }}</td>
                </tr>
            </tfoot>
        </table>
        @else
        <div class="no-data">
            No hay ventas registradas para este cliente.
        </div>
        @endif
    </div>

    <div class="footer">
        <script type="text/php">
            if (isset($pdf)) {
                $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
                $font = null;
                $size = 9;
                $color = array(0.5, 0.5, 0.5);
                $pdf->page_text(520, 820, $text, $font, $size, $color);
            }
        </script>
    </div>
</body>
</html>
