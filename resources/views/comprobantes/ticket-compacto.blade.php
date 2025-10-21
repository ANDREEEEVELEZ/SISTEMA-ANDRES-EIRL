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

        /* Información básica */
        .info {
            font-size: 10px;
            margin: 5px 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 2px 0;
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
    <button class="close-button no-print" onclick="window.close()">✖️ Cerrar</button>

    <div class="ticket">
        <!-- Encabezado empresa (compacto) -->
        <div class="empresa">
            <h2>{{ $empresa['nombre'] }}</h2>
            <p>RUC: {{ $empresa['ruc'] }}</p>
            <p>{{ $empresa['telefono'] }}</p>
        </div>

        <!-- Título -->
        <div class="ticket-titulo">
            <h3>TICKET DE VENTA</h3>
            <p class="bold">#{{ str_pad($venta->id, 6, '0', STR_PAD_LEFT) }}</p>
        </div>

        <!-- Información básica -->
        <div class="info">
            <div class="info-row">
                <span>{{ $venta->fecha_venta->format('d/m/Y') }}</span>
                <span>{{ $venta->hora_venta ? $venta->hora_venta->format('H:i') : $venta->created_at->format('H:i') }}</span>
            </div>
        </div>

        <div class="separator"></div>

        <!-- Cliente (compacto) -->
        <div class="cliente">
            <p class="bold">
                @if($venta->cliente)
                    {{ $venta->cliente->nombre_razon }}
                @else
                    Cliente General
                @endif
            </p>
            @if($venta->cliente && $venta->cliente->num_doc)
            <p>{{ $venta->cliente->tipo_doc }}: {{ $venta->cliente->num_doc }}</p>
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
        </div>

        <!-- Método de pago -->
        <div class="pago">
            <p>
                <span class="bold">Pago: </span>
                @switch($venta->metodo_pago)
                    @case('efectivo')
                        Efectivo
                        @break
                    @case('tarjeta')
                        Tarjeta
                        @break
                    @case('yape')
                        Yape
                        @break
                    @case('plin')
                        Plin
                        @break
                    @case('transferencia')
                        Transferencia
                        @break
                    @default
                        {{ ucfirst($venta->metodo_pago) }}
                @endswitch
            </p>
            @if($venta->cod_operacion)
            <p style="font-size: 9px;">Op: {{ $venta->cod_operacion }}</p>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="bold">¡Gracias por su compra!</p>
            <p style="font-size: 9px; margin-top: 3px;">Comprobante interno no válido para fines tributarios</p>
        </div>
    </div>

    <script>
        // Auto-imprimir al cargar (opcional)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 300);
        // };
    </script>
</body>
</html>
