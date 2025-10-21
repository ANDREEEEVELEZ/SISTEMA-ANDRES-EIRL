<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante #{{ $comprobante ? $comprobante->serie . '-' . str_pad($comprobante->correlativo, 8, '0', STR_PAD_LEFT) : $venta->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #fff;
        }

        .ticket {
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
            padding: 10px;
            background: white;
        }

        .center {
            text-align: center;
        }

        .left {
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .empresa {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }

        .empresa h2 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .empresa p {
            font-size: 11px;
            margin: 2px 0;
        }

        .comprobante-tipo {
            text-align: center;
            margin: 10px 0;
            padding: 10px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }

        .comprobante-tipo h3 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .info-section {
            margin: 10px 0;
            font-size: 11px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }

        .info-label {
            font-weight: bold;
        }

        .separator {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }

        .productos {
            margin: 10px 0;
        }

        .productos table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .productos th {
            border-bottom: 1px solid #000;
            padding: 5px 2px;
            text-align: left;
            font-weight: bold;
        }

        .productos td {
            padding: 5px 2px;
            border-bottom: 1px dotted #ccc;
        }

        .productos .cantidad {
            width: 15%;
            text-align: center;
        }

        .productos .descripcion {
            width: 55%;
        }

        .productos .precio {
            width: 15%;
            text-align: right;
        }

        .productos .total {
            width: 15%;
            text-align: right;
        }

        .totales {
            margin: 10px 0;
            font-size: 11px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }

        .total-final {
            font-size: 14px;
            font-weight: bold;
            margin-top: 5px;
            padding-top: 5px;
            border-top: 2px solid #000;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed #000;
            font-size: 11px;
        }

        .footer p {
            margin: 3px 0;
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
                padding: 5mm;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: 80mm auto;
                margin: 0;
            }
        }

        /* Botón de imprimir */
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
    <!-- Botones de acción (no se imprimen) -->
    <button class="print-button no-print" onclick="window.print()">🖨️ Imprimir</button>
    <button class="close-button no-print" onclick="window.close()">✖️ Cerrar</button>

    <div class="ticket">
        <!-- Encabezado de la empresa -->
        <div class="empresa">
            <h2>{{ $empresa['nombre'] }}</h2>
            <p>RUC: {{ $empresa['ruc'] }}</p>
            <p>{{ $empresa['direccion'] }}</p>
            <p>Tel: {{ $empresa['telefono'] }}</p>
            @if(isset($empresa['email']))
            <p>{{ $empresa['email'] }}</p>
            @endif
        </div>

        <!-- Tipo de comprobante -->
        <div class="comprobante-tipo">
            @if($comprobante)
                <h3>
                    @if($comprobante->tipo === 'boleta')
                        BOLETA DE VENTA ELECTRÓNICA
                    @elseif($comprobante->tipo === 'factura')
                        FACTURA ELECTRÓNICA
                    @elseif($comprobante->tipo === 'nota_credito')
                        NOTA DE CRÉDITO ELECTRÓNICA
                    @else
                        TICKET DE VENTA
                    @endif
                </h3>
                <p class="bold">{{ $comprobante->serie }}-{{ str_pad($comprobante->correlativo, 8, '0', STR_PAD_LEFT) }}</p>
            @else
                <h3>TICKET DE VENTA</h3>
                <p class="bold">#{{ str_pad($venta->id, 6, '0', STR_PAD_LEFT) }}</p>
            @endif
        </div>

        <!-- Información de la venta -->
        <div class="info-section">
            <div class="info-row">
                <span class="info-label">Fecha:</span>
                <span>{{ $venta->fecha_venta->format('d/m/Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Hora:</span>
                <span>{{ $venta->hora_venta ? $venta->hora_venta->format('H:i:s') : $venta->created_at->format('H:i:s') }}</span>
            </div>
            @if($venta->user)
            <div class="info-row">
                <span class="info-label">Vendedor:</span>
                <span>{{ $venta->user->name }}</span>
            </div>
            @endif
        </div>

        <div class="separator"></div>

        <!-- Información del cliente -->
        <div class="info-section">
            <p class="bold">CLIENTE:</p>
            @if($venta->cliente)
            <div class="info-row">
                <span>{{ $venta->cliente->nombre_razon }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ $venta->cliente->tipo_doc }}:</span>
                <span>{{ $venta->cliente->num_doc }}</span>
            </div>
            @if($venta->cliente->direccion)
            <div class="info-row">
                <span>{{ $venta->cliente->direccion }}</span>
            </div>
            @endif
            @else
            <p>Cliente General</p>
            @endif
        </div>

        <div class="separator"></div>

        <!-- Detalle de productos -->
        <div class="productos">
            <table>
                <thead>
                    <tr>
                        <th class="cantidad">CANT</th>
                        <th class="descripcion">DESCRIPCIÓN</th>
                        <th class="precio">P.U.</th>
                        <th class="total">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($venta->detalleVentas as $detalle)
                    <tr>
                        <td class="cantidad">{{ number_format($detalle->cantidad_venta, 0) }}</td>
                        <td class="descripcion">
                            {{ $detalle->producto ? $detalle->producto->nombre_producto : 'Producto N/A' }}
                        </td>
                        <td class="precio">{{ number_format($detalle->precio_unitario, 2) }}</td>
                        <td class="total">{{ number_format($detalle->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="separator"></div>

        <!-- Totales -->
        <div class="totales">
            <div class="total-row">
                <span>SUBTOTAL:</span>
                <span>S/ {{ number_format($venta->subtotal_venta, 2) }}</span>
            </div>
            <div class="total-row">
                <span>IGV (18%):</span>
                <span>S/ {{ number_format($venta->igv, 2) }}</span>
            </div>
            @if($venta->descuento_total > 0)
            <div class="total-row">
                <span>DESCUENTO:</span>
                <span>S/ {{ number_format($venta->descuento_total, 2) }}</span>
            </div>
            @endif
            <div class="total-row total-final">
                <span>TOTAL:</span>
                <span>S/ {{ number_format($venta->total_venta, 2) }}</span>
            </div>
        </div>

        <div class="separator"></div>

        <!-- Información de pago -->
        <div class="info-section">
            <div class="info-row">
                <span class="info-label">Método de Pago:</span>
                <span class="bold">
                    @switch($venta->metodo_pago)
                        @case('efectivo')
                            EFECTIVO
                            @break
                        @case('tarjeta')
                            TARJETA
                            @break
                        @case('yape')
                            YAPE
                            @break
                        @case('plin')
                            PLIN
                            @break
                        @case('transferencia')
                            TRANSFERENCIA
                            @break
                        @default
                            {{ strtoupper($venta->metodo_pago) }}
                    @endswitch
                </span>
            </div>
            @if($venta->cod_operacion)
            <div class="info-row">
                <span class="info-label">Cód. Operación:</span>
                <span>{{ $venta->cod_operacion }}</span>
            </div>
            @endif
        </div>

        <!-- Pie de página -->
        <div class="footer">
            <p class="bold">¡GRACIAS POR SU COMPRA!</p>
            @if(isset($empresa['web']))
            <p>{{ $empresa['web'] }}</p>
            @endif
            <p style="font-size: 9px; margin-top: 10px;">
                Representación impresa de comprobante electrónico
            </p>
            @if($comprobante && $comprobante->hash_sunat)
            <p style="font-size: 8px; word-break: break-all;">
                Hash: {{ substr($comprobante->hash_sunat, 0, 30) }}...
            </p>
            @endif
        </div>
    </div>

    <script>
        // Auto-imprimir al cargar (opcional, comentado por defecto)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 500);
        // };

        // Cerrar ventana después de imprimir (opcional)
        window.onafterprint = function() {
            // window.close();
        };
    </script>
</body>
</html>
