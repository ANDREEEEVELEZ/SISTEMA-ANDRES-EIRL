<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #{{ str_pad($venta->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            line-height: 1.2;
            color: #000;
            background: #fff;
        }

        .ticket {
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
            padding: 5px;
            background: white;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        /* Encabezado compacto */
        .empresa {
            text-align: center;
            margin-bottom: 5px;
            padding-bottom: 5px;
            border-bottom: 1px dashed #000;
        }

        .empresa h2 {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .empresa p {
            font-size: 9px;
            margin: 1px 0;
        }

        /* Título del ticket */
        .ticket-titulo {
            text-align: center;
            margin: 5px 0;
            padding: 5px 0;
            border-bottom: 1px dashed #000;
        }

        .ticket-titulo h3 {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .anulado {
            color: #cc0000;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            margin-top: 4px;
        }

        /* Información básica */
        .info {
            font-size: 10px;
            margin: 5px 0;
        }

        /* Usar grid para alinear estrictamente etiqueta (columna fija) y valor (columna flexible) */
        .info-row {
            display: grid;
            grid-template-columns: 75px 1fr; /* etiqueta fijo, valor flexible */
            gap: 4px;
            align-items: start;
            margin: 2px 0;
        }

        /* Para filas compactas (ej. método de pago), permitir que la etiqueta se ajuste
           al contenido y evitar saltos de línea entre etiqueta y valor */
        .info-row--compact {
            grid-template-columns: auto 1fr;
            gap: 8px;
        }

        .info-row--compact > .info-label,
        .info-row--compact > .value {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Excepción para filas que deben separar elementos a los extremos (Fecha/Hora) */
        .info-row.info-row--between {
            grid-template-columns: 1fr auto;
            gap: 0;
        }

        /* Etiqueta y valor: etiqueta en negrita, valor sin negrita */
        .info-row > span:first-child {
            font-weight: bold;
        }

        .info-row > .value {
            font-weight: normal;
        }
        .separator {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        /* Cliente compacto */
        .cliente {
            font-size: 10px;
            margin: 5px 0;
        }

        .cliente p {
            margin: 1px 0;
        }

        /* Productos compactos */
        .productos {
            margin: 5px 0;
            font-size: 10px;
        }

        .productos table {
            width: 100%;
            border-collapse: collapse;
        }

        .productos th {
            border-bottom: 1px solid #000;
            padding: 3px 1px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
        }

        .productos td {
            padding: 3px 1px;
            border-bottom: 1px dotted #ccc;
        }

        .productos .cant {
            width: 10%;
            text-align: center;
        }

        .productos .desc {
            width: 45%;
        }

        .productos .precio {
            width: 22%;
            text-align: right;
        }

        .productos .total {
            width: 23%;
            text-align: right;
        }

        /* Totales compactos */
        .totales {
            margin: 5px 0;
            font-size: 10px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 2px 0;
        }

        .total-final {
            font-size: 12px;
            font-weight: bold;
            margin-top: 3px;
            padding-top: 3px;
            border-top: 1px solid #000;
        }

        /* Pago */
        .pago {
            font-size: 10px;
            margin: 5px 0;
            text-align: center;
        }

        /* Footer compacto */
        .footer {
            text-align: center;
            margin-top: 8px;
            padding-top: 5px;
            border-top: 1px dashed #000;
            font-size: 10px;
        }

        .footer p {
            margin: 2px 0;
        }

        /* Estilos para impresión */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .ticket {
                width: 80mm;
                max-width: 80mm;
                margin: 0;
                padding: 3mm;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: 80mm auto;
                margin: 0;
            }
        }

        /* Botones de acción */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-family: Arial, sans-serif;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        .print-button:hover {
            background: #45a049;
        }

        .close-button {
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 10px 20px;
            background: #f44336;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-family: Arial, sans-serif;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        .close-button:hover {
            background: #da190b;
        }
    </style>
</head>
<body>
    <!-- Botones de acción -->
    <button class="print-button no-print" onclick="window.print()">🖨️ Imprimir</button>
    <button class="close-button no-print" onclick="cerrarVentana()">✖️ Cerrar</button>

    <div class="ticket">
        <!-- Encabezado empresa (compacto) -->
        <div class="empresa">
            <h2>{{ $empresa['nombre'] }}</h2>
            <p>RUC: {{ $empresa['ruc'] }}</p>
            @if(isset($comprobante) && $comprobante->tipo === 'ticket')
                <p>AV. RAMON CASTILLA NRO 123 CERCADO</p>
            @else
                <p>{{ $empresa['telefono'] }}</p>
            @endif
        </div>

        <!-- Título -->
        <div class="ticket-titulo">
            <h3>TICKET DE VENTA</h3>
            @if($comprobante)
                <p class="bold">{{ $comprobante->serie }}-{{ str_pad($comprobante->correlativo, 8, '0', STR_PAD_LEFT) }}</p>
                @if($comprobante->estado === 'anulado')
                    <p class="anulado">ANULADO</p>
                @endif
            @else
                <p class="bold">#{{ str_pad($venta->id, 6, '0', STR_PAD_LEFT) }}</p>
            @endif
        </div>

        <!-- Información básica -->
        <div class="info">
            <div class="info-row info-row--between">
                <span>Fecha: {{ $venta->fecha_venta->format('d/m/Y') }}</span>
                <span>Hora: {{ $venta->hora_venta ? $venta->hora_venta->format('H:i:s') : $venta->created_at->format('H:i:s') }}</span>
            </div>
        </div>

        <div class="separator"></div>

        <!-- Cliente (compacto) -->
        <div class="cliente">
            @if(!empty($venta->nombre_cliente_temporal))
                <div class="info-row">
                    <span class="bold">Cliente:</span>
                    <span class="value">{{ $venta->nombre_cliente_temporal }}</span>
                </div>
            @elseif($venta->cliente)
                <div class="info-row">
                    <span class="bold">Cliente:</span>
                    <span class="value">{{ $venta->cliente->nombre_razon }}</span>
                </div>
                <div class="info-row">
                    <span class="bold">{{ $venta->cliente->tipo_doc }}:</span>
                    <span class="value">{{ $venta->cliente->num_doc }}</span>
                </div>
                <div class="info-row">
                    <span class="bold">Dirección:</span>
                    <span class="value">@if($venta->cliente->tipo_doc === 'DNI') - @else {{ $venta->cliente->direccion ?? '-' }} @endif</span>
                </div>
            @else
                <div class="info-row">
                    <span class="bold">Cliente:</span>
                    <span class="value">Cliente General</span>
                </div>
            @endif

            @if(isset($comprobante) && in_array($comprobante->tipo, ['boleta', 'factura']))
            <div class="info-row">
                <span class="bold">Forma de pago:</span>
                <span class="value">Contado</span>
            </div>
            @endif
        </div>

        <div class="separator"></div>

        <!-- Productos (compacto con precio unitario) -->
        <div class="productos">
            <table>
                <thead>
                    <tr>
                        <th class="cant">CANT</th>
                        <th class="desc">PRODUCTO</th>
                        <th class="precio">P.U.</th>
                        <th class="total">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($venta->detalleVentas as $detalle)
                    <tr>
                        <td class="cant">{{ number_format($detalle->cantidad_venta, 0) }}</td>
                        <td class="desc">{{ $detalle->producto ? $detalle->producto->nombre_producto : 'N/A' }}</td>
                        <td class="precio">{{ number_format($detalle->precio_unitario, 2) }}</td>
                        <td class="total">{{ number_format($detalle->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="separator"></div>

        <!-- Totales (compacto) -->
        <div class="totales">
            @if(isset($comprobante) && in_array($comprobante->tipo, ['boleta', 'factura']))
                {{-- Desglose completo para Factura y Boleta --}}
                <div class="total-row">
                    <span>OP. EXONERADA</span>
                    <span>S/ 0.00</span>
                </div>
                <div class="total-row">
                    <span>OP. INAFECTA</span>
                    <span>S/ 0.00</span>
                </div>
                <div class="total-row">
                    <span>OP. GRAVADA</span>
                    <span>S/ {{ number_format($venta->subtotal_venta, 2) }}</span>
                </div>
                <div class="total-row">
                    <span>I.G.V. 18%</span>
                    <span>S/ {{ number_format($venta->igv, 2) }}</span>
                </div>
                <div class="separator"></div>
                <div class="total-row total-final">
                    <span class="bold">TOTAL A PAGAR</span>
                    <span class="bold">S/ {{ number_format($venta->total_venta, 2) }}</span>
                </div>
                <div class="separator"></div>
                @if($venta->monto_pagado)
                <div class="total-row">
                    <span>MONTO PAGADO:</span>
                    <span>S/ {{ number_format($venta->monto_pagado, 2) }}</span>
                </div>
                <div class="total-row">
                    <span>VUELTO:</span>
                    <span>S/ {{ number_format($venta->vuelto ?? 0, 2) }}</span>
                </div>
                @endif
            @else
                {{-- Formato simple para Ticket de venta --}}
                @if($venta->descuento_total > 0)
                <div class="total-row">
                    <span>Descuento:</span>
                    <span>-S/ {{ number_format($venta->descuento_total, 2) }}</span>
                </div>
                @endif
                <div class="total-row total-final">
                    <span class="bold">TOTAL A PAGAR:</span>
                    <span class="bold">S/ {{ number_format($venta->total_venta, 2) }}</span>
                </div>
                <div class="separator"></div>
                @if($venta->monto_pagado)
                <div class="total-row">
                    <span>MONTO PAGADO:</span>
                    <span>S/ {{ number_format($venta->monto_pagado, 2) }}</span>
                </div>
                <div class="total-row">
                    <span>VUELTO:</span>
                    <span>S/ {{ number_format($venta->vuelto ?? 0, 2) }}</span>
                </div>
                @endif
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="bold">¡Gracias por su compra!</p>
            <p style="font-size: 9px; margin-top: 3px;">Comprobante interno no válido para fines tributarios</p>
        </div>
    </div>

    <script>
        // Función para cerrar la ventana o volver atrás
        function cerrarVentana() {
            // Redirigir directamente a crear una nueva venta
            window.location.href = '{{ route("filament.admin.resources.ventas.create") }}';
        }

        // Auto-imprimir al cargar (opcional)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 300);
        // };
    </script>
</body>
</html>
