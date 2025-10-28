<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Arqueo de Caja - {{ $arqueo->id }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 10px; }
        .section { margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px; border: 1px solid #ddd; }
        .right { text-align: right; }
        .big { font-size: 16px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Reporte de Arqueo de Caja</h2>
        <div>Arqueo #: {{ $arqueo->id }} — Caja: {{ $arqueo->caja->id ?? '-' }} — Usuario: {{ $arqueo->user->name ?? '-' }}</div>
        <div>Fecha: {{ $arqueo->created_at->format('d/m/Y H:i') }}</div>
    </div>

    <div class="section">
        <table>
            <tr>
                <th>Periodo desde</th>
                <th>Periodo hasta</th>
                <th>Saldo inicial</th>
            </tr>
            <tr>
                <td>{{ $arqueo->fecha_inicio->format('d/m/Y H:i') }}</td>
                <td>{{ $arqueo->fecha_fin->format('d/m/Y H:i') }}</td>
                <td class="right">S/ {{ number_format($arqueo->saldo_inicial,2) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <table>
            <tr>
                <th>Total ventas (efectivo)</th>
                <th>Total ingresos</th>
                <th>Total egresos</th>
                <th>Saldo teórico</th>
            </tr>
            <tr>
                <td class="right">S/ {{ number_format($arqueo->total_ventas,2) }}</td>
                <td class="right">S/ {{ number_format($arqueo->total_ingresos,2) }}</td>
                <td class="right">S/ {{ number_format($arqueo->total_egresos,2) }}</td>
                <td class="right big">S/ {{ number_format($arqueo->saldo_teorico,2) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <table>
            <tr>
                <th>Saldo físico contado</th>
                <th>Diferencia</th>
            </tr>
            <tr>
                <td class="right">S/ {{ number_format($arqueo->efectivo_contado ?? 0,2) }}</td>
                <td class="right">S/ {{ number_format($arqueo->diferencia ?? 0,2) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <strong>Observación:</strong>
        <div>{{ $arqueo->observacion ?? '—' }}</div>
    </div>

    <div class="section">
        <small>Generado por sistema — Usuario: {{ $arqueo->user->name ?? '-' }}</small>
    </div>
</body>
</html>
