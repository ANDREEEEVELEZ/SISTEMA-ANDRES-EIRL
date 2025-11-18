<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Ventas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            color: #333;
            padding: 15px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 18px;
            color: #1e40af;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 10px;
            color: #64748b;
        }

        .filtros {
            background-color: #f8fafc;
            padding: 8px 12px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #e2e8f0;
        }

        .filtros h3 {
            font-size: 11px;
            color: #1e40af;
            margin-bottom: 6px;
        }

        .filtros-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }

        .filtro-item {
            font-size: 9px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .filtro-item strong {
            color: #475569;
        }

        .filtro-item span {
            color: #1e293b;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }

        table thead {
            background-color: #1e40af;
            color: white;
        }

        table thead th {
            padding: 8px 6px;
            text-align: left;
            font-weight: 600;
            font-size: 10px;
        }

        table tbody tr {
            border-bottom: 1px solid #e2e8f0;
        }

        table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        table tbody tr:hover {
            background-color: #f1f5f9;
        }

        table tbody td {
            padding: 7px 6px;
            font-size: 10px;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
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

        .totales {
            background-color: #1e40af;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-top: 15px;
        }

        .totales-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            text-align: center;
        }

        .total-item h4 {
            font-size: 9px;
            margin-bottom: 5px;
            opacity: 0.9;
        }

        .total-item p {
            font-size: 14px;
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #64748b;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE VENTAS</h1>
        <p>{{ config('app.name', 'Sistema de Ventas') }}</p>
        <p style="font-size: 8px; margin-top: 3px;">Generado el {{ $fechaGeneracion->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="filtros">
        <h3>Filtros Aplicados:</h3>
        <div class="filtros-grid">
            <div class="filtro-item">
                <strong>Tipo Comprobante:</strong>
                <span>{{ ucfirst($filtros['tipo_comprobante']) }}</span>
            </div>
            <div class="filtro-item">
                <strong>Fecha Inicio:</strong>
                <span>{{ $filtros['fecha_inicio'] ? \Carbon\Carbon::parse($filtros['fecha_inicio'])->format('d/m/Y') : 'N/A' }}</span>
            </div>
            <div class="filtro-item">
                <strong>Fecha Fin:</strong>
                <span>{{ $filtros['fecha_fin'] ? \Carbon\Carbon::parse($filtros['fecha_fin'])->format('d/m/Y') : 'N/A' }}</span>
            </div>
            <div class="filtro-item">
                <strong>Tipo Cliente:</strong>
                <span>{{ strtoupper($filtros['tipo_cliente']) }}</span>
            </div>
            <div class="filtro-item">
                <strong>Estado Comprobante:</strong>
                <span>{{ ucfirst($filtros['estado_comprobante']) }}</span>
            </div>
        </div>
    </div>

    <!-- Estadísticas resumen (debajo de filtros aplicados) -->
    <table style="width:100%; border-collapse:collapse; margin-top:10px; margin-bottom:10px;">
        <tr>
            <td style="width:25%; padding:6px; vertical-align:top;">
                <div style="background:#ffffff; border:1px solid #e2e8f0; padding:8px; border-radius:6px;">
                    <div style="font-size:9px; color:#475569;">Total de Ventas</div>
                    <div style="font-size:16px; font-weight:700; margin-top:6px;">S/ {{ number_format((float)($totalVentas ?? 0), 2) }}</div>
                    <div style="font-size:10px; color:#64748b; margin-top:4px;">{{ ($cantidadVentas ?? 0) }} ventas {{ (!empty($filtros['fecha_inicio']) || !empty($filtros['fecha_fin'])) ? 'en el rango de fecha' : 'hoy (' . now()->format('d/m/Y') . ')' }}</div>
                </div>
            </td>

            <td style="width:25%; padding:6px; vertical-align:top;">
                <div style="background:#ffffff; border:1px solid #e2e8f0; padding:8px; border-radius:6px;">
                    <div style="font-size:9px; color:#475569;">Total Facturas</div>
                    <div style="font-size:16px; font-weight:700; margin-top:6px;">S/ {{ number_format((float)($totalFacturas ?? 0), 2) }}</div>
                    <div style="font-size:10px; color:#64748b; margin-top:4px;">{{ ($cantidadFacturas ?? 0) }} facturas emitidas {{ (!empty($filtros['fecha_inicio']) || !empty($filtros['fecha_fin'])) ? 'en el rango de fecha' : 'hoy (' . now()->format('d/m/Y') . ')' }}</div>
                </div>
            </td>

            <td style="width:25%; padding:6px; vertical-align:top;">
                <div style="background:#ffffff; border:1px solid #e2e8f0; padding:8px; border-radius:6px;">
                    <div style="font-size:9px; color:#475569;">Total Boletas</div>
                    <div style="font-size:16px; font-weight:700; margin-top:6px;">S/ {{ number_format((float)($totalBoletas ?? 0), 2) }}</div>
                    <div style="font-size:10px; color:#64748b; margin-top:4px;">{{ ($cantidadBoletas ?? 0) }} boletas emitidas {{ (!empty($filtros['fecha_inicio']) || !empty($filtros['fecha_fin'])) ? 'en el rango de fecha' : 'hoy (' . now()->format('d/m/Y') . ')' }}</div>
                </div>
            </td>

            <td style="width:25%; padding:6px; vertical-align:top;">
                <div style="background:#ffffff; border:1px solid #e2e8f0; padding:8px; border-radius:6px;">
                    <div style="font-size:9px; color:#475569;">Total Tickets</div>
                    <div style="font-size:16px; font-weight:700; margin-top:6px;">S/ {{ number_format((float)($totalTickets ?? 0), 2) }}</div>
                    <div style="font-size:10px; color:#64748b; margin-top:4px;">{{ ($cantidadTickets ?? 0) }} tickets emitidos {{ (!empty($filtros['fecha_inicio']) || !empty($filtros['fecha_fin'])) ? 'en el rango de fecha' : 'hoy (' . now()->format('d/m/Y') . ')' }}</div>
                </div>
            </td>
        </tr>
        <tr>
            <td style="width:25%; padding:6px; vertical-align:top;">
                <div style="background:#fff6f6; border:1px solid #fee2e2; padding:8px; border-radius:6px;">
                    <div style="font-size:9px; color:#b91c1c;">Total Ventas Anuladas</div>
                    <div style="font-size:16px; font-weight:700; margin-top:6px; color:#b91c1c;">S/ {{ number_format((float)($totalVentasAnuladas ?? 0), 2) }}</div>
                    <div style="font-size:10px; color:#7f1d1d; margin-top:4px;">{{ ($cantidadVentasAnuladas ?? 0) }} ventas anuladas</div>
                </div>
            </td>

            <td style="width:25%; padding:6px; vertical-align:top;">
                <div style="background:#fff6f6; border:1px solid #fee2e2; padding:8px; border-radius:6px;">
                    <div style="font-size:9px; color:#b91c1c;">Facturas Anuladas</div>
                    <div style="font-size:16px; font-weight:700; margin-top:6px; color:#b91c1c;">S/ {{ number_format((float)($totalFacturasAnuladas ?? 0), 2) }}</div>
                    <div style="font-size:10px; color:#7f1d1d; margin-top:4px;">{{ ($cantidadFacturasAnuladas ?? 0) }} facturas anuladas</div>
                </div>
            </td>

            <td style="width:25%; padding:6px; vertical-align:top;">
                <div style="background:#fff6f6; border:1px solid #fee2e2; padding:8px; border-radius:6px;">
                    <div style="font-size:9px; color:#b91c1c;">Boletas Anuladas</div>
                    <div style="font-size:16px; font-weight:700; margin-top:6px; color:#b91c1c;">S/ {{ number_format((float)($totalBoletasAnuladas ?? 0), 2) }}</div>
                    <div style="font-size:10px; color:#7f1d1d; margin-top:4px;">{{ ($cantidadBoletasAnuladas ?? 0) }} boletas anuladas</div>
                </div>
            </td>

            <td style="width:25%; padding:6px; vertical-align:top;">
                <div style="background:#fff6f6; border:1px solid #fee2e2; padding:8px; border-radius:6px;">
                    <div style="font-size:9px; color:#b91c1c;">Tickets Anulados</div>
                    <div style="font-size:16px; font-weight:700; margin-top:6px; color:#b91c1c;">S/ {{ number_format((float)($totalTicketsAnuladas ?? 0), 2) }}</div>
                    <div style="font-size:10px; color:#7f1d1d; margin-top:4px;">{{ ($cantidadTicketsAnuladas ?? 0) }} tickets anulados</div>
                </div>
            </td>
        </tr>
    </table>

    @if($ventas->count() > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 10%;">Fecha</th>
                    <th style="width: 16%;">Comprobante</th>
                    <th style="width: 11%;">Estado</th>
                    <th style="width: 25%;">Cliente</th>
                    <th style="width: 11%;">Documento</th>
                    <th style="width: 8%;" class="text-right">Subtotal</th>
                    <th style="width: 6%;" class="text-right">IGV</th>
                    <th style="width: 9%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ventas as $index => $venta)
                    @php
                        $comprobante = $venta->comprobantes->whereNotIn('tipo', ['nota de credito', 'nota de debito'])->first();
                        $esAnulada = ($venta->estado_venta === 'anulada');
                    @endphp
                    <tr @if($esAnulada) style="opacity: 0.6; background-color: #fee2e2 !important;" @endif>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y') }}</td>
                        <td>
                            @if($comprobante)
                                <strong>{{ strtoupper($comprobante->tipo) }}</strong><br>
                                <small>{{ $comprobante->serie }}-{{ $comprobante->correlativo }}</small>
                            @else
                                <span class="badge badge-danger">Sin Comprobante</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($comprobante)
                                @if($comprobante->estado === 'emitido')
                                    <span class="badge badge-success">Emitido</span>
                                @elseif($comprobante->estado === 'anulado')
                                    <span class="badge badge-warning">Anulado</span>
                                @elseif($comprobante->estado === 'rechazado')
                                    <span class="badge badge-danger">Rechazado</span>
                                @endif
                            @else
                                <span class="badge badge-danger">N/A</span>
                            @endif
                        </td>
                        <td>{{ $venta->cliente->nombre_razon ?? 'N/A' }}</td>
                        <td>
                            <small>{{ $venta->cliente->tipo_doc ?? 'N/A' }}</small><br>
                            {{ $venta->cliente->num_doc ?? 'N/A' }}
                        </td>
                        <td class="text-right">
                            @if($esAnulada)
                                <span style="color: #dc2626;">-S/ {{ number_format((float)$venta->subtotal_venta, 2) }}</span>
                            @else
                                S/ {{ number_format((float)$venta->subtotal_venta, 2) }}
                            @endif
                        </td>
                        <td class="text-right">
                            @if($esAnulada)
                                <span style="color: #dc2626;">-S/ {{ number_format((float)$venta->igv, 2) }}</span>
                            @else
                                S/ {{ number_format((float)$venta->igv, 2) }}
                            @endif
                        </td>
                        <td class="text-right">
                            @if($esAnulada)
                                <strong style="color: #dc2626;">-S/ {{ number_format((float)$venta->total_venta, 2) }}</strong>
                            @else
                                <strong>S/ {{ number_format((float)$venta->total_venta, 2) }}</strong>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totales">
            <div class="totales-grid">
                <div class="total-item">
                    <h4>SUBTOTAL GENERAL</h4>
                    <p>S/ {{ number_format((float)$subtotalGeneral, 2) }}</p>
                </div>
                <div class="total-item">
                    <h4> IGV GENERAL</h4>
                    <p>S/ {{ number_format((float)$igvGeneral, 2) }}</p>
                </div>
                <div class="total-item">
                    <h4>TOTAL GENERAL</h4>
                    <p>S/ {{ number_format((float)$totalGeneral, 2) }}</p>
                </div>
            </div>
            @if($cantidadAnuladas > 0)
                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.3); font-size: 9px; text-align: center;">
                     <strong>Ventas Anuladas:</strong> {{ $cantidadAnuladas }} venta(s) por S/ {{ number_format((float)$montoAnulado, 2) }}
                    <span style="display: block; margin-top: 3px; opacity: 0.9;">(NO incluidas en el total general - se muestran solo para referencia)</span>
                </div>
            @endif
        </div>

        <div style="margin-top: 15px; font-size: 8px; color: #475569;">
            <strong>Total de registros:</strong> {{ $ventas->count() }} venta(s)
            @if($cantidadAnuladas > 0)
                <span style="color: #dc2626;"> ({{ $cantidadAnuladas }} anulada(s))</span>
            @endif
        </div>
    @else
        <div class="no-data">
            <p>No se encontraron ventas con los filtros aplicados.</p>
        </div>
    @endif

    <div class="footer">
        <p>Este es un documento generado automáticamente por el sistema.</p>
        <p>{{ config('app.name', 'Sistema de Ventas') }} - {{ now()->format('Y') }}</p>
    </div>
</body>
</html>
