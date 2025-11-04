<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Exportación de cajas</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 10px; }
        .caja { margin-bottom: 18px; page-break-inside: avoid; }
        .section-title { font-weight: bold; margin-top: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f4f4f4; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Exportación de información de cajas</h1>
        <div>
            @if($inicio->format('Y-m-d') === $fin->format('Y-m-d'))
                Periodo: {{ $inicio->format('d/m/Y') }}
            @else
                Periodo: {{ $inicio->format('d/m/Y') }} - {{ $fin->format('d/m/Y') }}
            @endif
        </div>
    </div>

    @foreach($reportes as $r)
        <div class="caja">
            <h2>Caja #{{ $r['caja']->numero_secuencial }} - {{ $r['caja']->nombre ?? '' }}</h2>

            @if($incluir_resumen)
                <div class="section-title">Resumen</div>

                <table>
                    <tr>
                        <th>Fecha apertura</th>
                        <th>Fecha cierre</th>
                        <th>Estado</th>
                        <th>Observación</th>
                    </tr>
                    <tr>
                        <td>{{ optional($r['caja']->fecha_apertura)->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>{{ optional($r['caja']->fecha_cierre)->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>{{ $r['caja']->estado ?? '-' }}</td>
                        <td>{{ $r['caja']->observacion ?? '-' }}</td>
                    </tr>
                </table>

                <table>
                    <tr>
                        <th>Saldo inicial</th>
                        <th>Total ventas (efectivo)</th>
                        <th>Total ingresos</th>
                        <th>Total egresos</th>
                        <th>Saldo teórico</th>
                        <th>Saldo final</th>
                    </tr>
                    <tr>
                        <td>S/ {{ number_format($r['caja']->saldo_inicial ?? 0, 2) }}</td>
                        <td>S/ {{ number_format($r['total_ventas'] ?? 0, 2) }}</td>
                        <td>S/ {{ number_format($r['total_ingresos'] ?? 0, 2) }}</td>
                        <td>S/ {{ number_format($r['total_egresos'] ?? 0, 2) }}</td>
                        <td>S/ {{ number_format((($r['caja']->saldo_inicial ?? 0) + ($r['total_ventas'] ?? 0) + ($r['total_ingresos'] ?? 0) - ($r['total_egresos'] ?? 0)), 2) }}</td>
                        <td>S/ {{ number_format($r['caja']->saldo_final ?? (($r['caja']->saldo_inicial ?? 0) + ($r['total_ventas'] ?? 0) + ($r['total_ingresos'] ?? 0) - ($r['total_egresos'] ?? 0)), 2) }}</td>
                    </tr>
                </table>
            @endif

            @if($incluir_movimientos)
                <div class="section-title">Movimientos</div>
                @if(count($r['movimientos']) === 0)
                    <div>No hay movimientos en el periodo seleccionado.</div>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Descripción</th>
                                <th>Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($r['movimientos'] as $m)
                                <tr>
                                    <td>{{ optional($m->fecha_movimiento)->format('d/m/Y H:i') }}</td>
                                    <td>{{ ucfirst($m->tipo) }}</td>
                                    <td>{{ $m->descripcion ?? '-' }}</td>
                                    <td>S/ {{ number_format($m->monto ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endif
        </div>
    @endforeach

    <div style="text-align:center;margin-top:20px;font-size:10px;color:#666;">Generado: {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
