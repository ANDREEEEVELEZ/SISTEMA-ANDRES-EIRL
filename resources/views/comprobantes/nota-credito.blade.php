<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ strtoupper($nota->tipo) }} #{{ $nota->serie }}-{{ str_pad($nota->correlativo, 8, '0', STR_PAD_LEFT) }}</title>
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
            color: #ff6600;
        }

        .info-section {
            margin: 10px 0;
            font-size: 11px;
        }

        .info-row {
            display: grid;
            grid-template-columns: 75px 1fr;
            gap: 4px;
            align-items: start;
            margin: 3px 0;
        }

        .info-row--compact {
            grid-template-columns: auto 1fr;
            gap: 8px;
        }

        .info-row--between {
            grid-template-columns: 1fr auto;
            gap: 0;
        }

        .info-label {
            font-weight: bold;
        }

        .separator {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }

        .motivo-box {
            background: #fff5e6;
            border: 1px solid #ff6600;
            padding: 8px;
            margin: 10px 0;
            font-size: 11px;
        }

        .motivo-box .label {
            font-weight: bold;
            color: #ff6600;
            display: block;
            margin-bottom: 4px;
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
            color: #ff6600;
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

            .motivo-box {
                background: white;
                border: 1px solid #000;
            }
        }

        /* Botones de acción */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #ff6600;
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
            background: #cc5200;
        }

        .close-button {
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 10px 20px;
            background: #2196F3;
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
            background: #0b7dda;
        }

        .list-button {
            position: fixed;
            top: 20px;
            left: 200px;
            padding: 10px 20px;
            background: #FF9800;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-family: Arial, sans-serif;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        .list-button:hover {
            background: #e68900;
        }
    </style>
</head>
<body>
    <!-- Botones de acción (no se imprimen) -->
    <button class="print-button no-print" onclick="window.print()">🖨️ Imprimir</button>
    <button class="close-button no-print" onclick="window.close()">Registrar nueva venta</button>
    <button class="list-button no-print" onclick="window.location.href='{{ route('filament.admin.resources.ventas.index') }}'">📋 Listado de ventas</button>

    <div class="ticket">
        <!-- Encabezado de la empresa -->
        <div class="empresa">
            <h2>{{ $empresa['nombre'] }}</h2>
            <p>RUC: {{ $empresa['ruc'] }}</p>
            <p>{{ $empresa['direccion'] }}</p>
           <!-- <p>Tel: {{ $empresa['telefono'] }}</p>
            @if(isset($empresa['email']))
            <p>{{ $empresa['email'] }}</p>
            @endif-->
        </div>

        <!-- Tipo de comprobante -->
        <div class="comprobante-tipo">
            <h3>
                @if($nota->tipo === 'nota de credito')
                    NOTA DE CRÉDITO ELECTRÓNICA
                @else
                    NOTA DE DÉBITO ELECTRÓNICA
                @endif
            </h3>
            <p class="bold">{{ $nota->serie }}-{{ str_pad($nota->correlativo, 8, '0', STR_PAD_LEFT) }}</p>
        </div>

        <!-- Información de la nota -->
        <div class="info-section">
            <div class="info-row info-row--between">
                <span class="info-label">Fecha: {{ \Carbon\Carbon::parse($nota->fecha_emision)->format('d/m/Y') }}</span>
                <span class="info-label">Hora: {{ \Carbon\Carbon::parse($nota->fecha_emision)->format('H:i:s') }}</span>
            </div>
        </div>

        <div class="separator"></div>

        <!-- Información del comprobante que se modifica -->
        <div class="info-section">
            <p class="bold center" style="margin-bottom: 5px;">COMPROBANTE QUE SE MODIFICA</p>
            @if($comprobanteOriginal)
            <div class="info-row">
                <span class="bold">Tipo:</span>
                <span class="value">{{ strtoupper($comprobanteOriginal->tipo) }}</span>
            </div>
            <div class="info-row">
                <span class="bold">Número:</span>
                <span class="value">{{ $comprobanteOriginal->serie }}-{{ str_pad($comprobanteOriginal->correlativo, 8, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="info-row">
                <span class="bold">Fecha:</span>
                <span class="value">{{ \Carbon\Carbon::parse($comprobanteOriginal->fecha_emision)->format('d/m/Y') }}</span>
            </div>
            @endif
        </div>

        <div class="separator"></div>

        <!-- Motivo de la nota -->
        @if($nota->motivo_anulacion || $nota->codigo_tipo_nota)
        <div class="motivo-box">
            <span class="label">MOTIVO:</span>
            @if($nota->codigo_tipo_nota)
                <p style="margin-bottom: 4px;">
                    <strong>Tipo {{ $nota->codigo_tipo_nota }}:</strong>
                    @switch($nota->codigo_tipo_nota)
                        @case('01')
                            Anulación de la operación
                            @break
                        @case('02')
                            Anulación por error en el RUC
                            @break
                        @case('03')
                            Corrección por error en la descripción
                            @break
                        @case('04')
                            Descuento global
                            @break
                        @case('05')
                            Descuento por ítem
                            @break
                        @case('06')
                            Devolución total
                            @break
                        @case('07')
                            Devolución por ítem
                            @break
                        @case('08')
                            Bonificación
                            @break
                        @case('09')
                            Disminución en el valor
                            @break
                        @case('10')
                            Otros conceptos
                            @break
                        @case('11')
                            Ajustes de operaciones de exportación
                            @break
                        @case('12')
                            Ajustes afectos al IVAP
                            @break
                        @case('13')
                            Corrección monto neto de operaciones gravadas
                            @break
                        @default
                            Código {{ $nota->codigo_tipo_nota }}
                    @endswitch
                </p>
            @endif
            @if($nota->motivo_anulacion)
                <p>{{ $nota->motivo_anulacion }}</p>
            @endif
        </div>
        @endif

        <div class="separator"></div>

        <!-- Información del cliente -->
        <div class="info-section">
            @if($nota->venta->cliente)
            <div class="info-row">
                <span class="bold">Cliente:</span>
                <span class="value">{{ $nota->venta->cliente->nombre_razon }}</span>
            </div>
            <div class="info-row">
                <span class="bold">{{ $nota->venta->cliente->tipo_doc }}:</span>
                <span class="value">{{ $nota->venta->cliente->num_doc }}</span>
            </div>
            <div class="info-row">
                <span class="bold">Dirección:</span>
                <span class="value">{{ $nota->venta->cliente->direccion ?? '-' }}</span>
            </div>
            @else
            <div class="info-row">
                <span class="bold">Cliente:</span>
                <span class="value">Cliente General</span>
            </div>
            @endif
        </div>

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
                    @foreach($nota->venta->detalleVentas as $detalle)
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
                <span>OP. EXONERADA</span>
                <span>S/ 0.00</span>
            </div>
            <div class="total-row">
                <span>OP. INAFECTA</span>
                <span>S/ 0.00</span>
            </div>
            <div class="total-row">
                <span>OP. GRAVADA</span>
                <span>S/ {{ number_format($nota->sub_total, 2) }}</span>
            </div>
            <div class="total-row">
                <span>I.G.V. 18%</span>
                <span>S/ {{ number_format($nota->igv, 2) }}</span>
            </div>
            <div class="separator"></div>
            <div class="total-row total-final">
                <span>TOTAL {{ $nota->tipo === 'nota de credito' ? 'A DEVOLVER' : 'A COBRAR' }}</span>
                <span>S/ {{ number_format($nota->total, 2) }}</span>
            </div>
        </div>

        <!-- Pie de página -->
        <div class="footer">
            <p class="bold">{{ $nota->tipo === 'nota de credito' ? '¡NOTA DE CRÉDITO EMITIDA!' : '¡NOTA DE DÉBITO EMITIDA!' }}</p>
            @if(isset($empresa['web']))
            <p>{{ $empresa['web'] }}</p>
            @endif

            @if(isset($qrText) && $qrText)
            <div class="center" style="margin: 10px 0;">
                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(80)->generate($qrText) !!}
            </div>
            @endif

            <p style="font-size: 9px; margin-top: 10px;">
                Representación impresa de comprobante electrónico. Consulte su documento con su clave sol
            </p>
        </div>
    </div>

    <script>
        // Cerrar ventana
        function cerrarVentana() {
            window.close();
        }

        // Cerrar ventana después de imprimir (opcional)
        window.onafterprint = function() {
            // window.close();
        };
    </script>
</body>
</html>
