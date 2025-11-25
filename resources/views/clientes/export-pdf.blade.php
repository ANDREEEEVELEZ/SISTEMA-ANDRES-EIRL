<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Arial', sans-serif; font-size: 10px; color: #333; padding: 15px; }

        .header { text-align: center; margin-bottom: 18px; border-bottom: 3px solid #2563eb; padding-bottom: 8px; }
        .header h1 { font-size: 18px; color: #1e40af; margin-bottom: 4px; }
        .header p { font-size: 10px; color: #64748b; }

        .export-info { text-align: right; font-size: 9px; color: #64748b; margin: 8px 0 12px; }

        .filtros { background-color: #f8fafc; padding: 8px 10px; margin-bottom: 12px; border-radius: 5px; border: 1px solid #e2e8f0; }
        .filtros strong { color: #475569; font-size: 9px; }
        .filtros span { color: #1e293b; font-weight: 600; font-size: 9px; }

        table { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 10px; }
        thead { background-color: #1e40af; color: #fff; }
        thead th { padding: 8px 6px; text-align: left; font-weight: 600; font-size: 10px; }
        tbody tr { border-bottom: 1px solid #e2e8f0; }
        tbody tr:nth-child(even) { background-color: #f8fafc; }
        tbody td { padding: 7px 6px; font-size: 10px; }

        .no-data { text-align: center; padding: 30px; color: #64748b; font-size: 11px; }

        .footer { margin-top: 18px; text-align: center; font-size: 9px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>CLIENTES</h1>
        <p>{{ config('app.name', 'Sistema') }}</p>
        <p style="font-size:8px; margin-top:4px;">Generado el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="filtros">
        <p style="font-size:9px; color:#475569; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
            <strong>Tipo Doc:</strong> <span style="color:#1e293b; font-weight:600;">{{ request('tipo_doc') ?? 'Todos' }}</span>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <strong>Estado:</strong> <span style="color:#1e293b; font-weight:600;">{{ request('estado') ?? 'Todos' }}</span>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <strong>Fecha Desde:</strong> <span style="color:#1e293b; font-weight:600;">{{ request('fecha_desde') ? \Carbon\Carbon::parse(request('fecha_desde'))->format('d/m/Y') : 'N/A' }}</span>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <strong>Fecha Hasta:</strong> <span style="color:#1e293b; font-weight:600;">{{ request('fecha_hasta') ? \Carbon\Carbon::parse(request('fecha_hasta'))->format('d/m/Y') : 'N/A' }}</span>
        </p>
    </div>

    @if($clientes->count() > 0)
        <table>
            <thead>
                <tr>
                    <th style="width:6%;">#</th>
                    <th style="width:12%;">Tipo Doc</th>
                    <th style="width:16%;">N° Doc</th>
                    <th style="width:38%;">Nombre / Razón Social</th>
                    <th style="width:14%;">Tipo Cliente</th>
                    <th style="width:14%;">Fecha Registro</th>
                </tr>
            </thead>
            <tbody>
                @foreach($clientes as $i => $cliente)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ strtoupper($cliente->tipo_doc) }}</td>
                        <td>{{ $cliente->num_doc }}</td>
                        <td>{{ $cliente->nombre_razon }}</td>
                        <td>
                            @switch($cliente->tipo_cliente)
                                @case('natural') Persona Natural @break
                                @case('natural_con_negocio') Natural con Negocio @break
                                @case('juridica') Persona Jurídica @break
                                @default {{ ucfirst($cliente->tipo_cliente) }}
                            @endswitch
                        </td>
                        <td>{{ \Carbon\Carbon::parse($cliente->fecha_registro)->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top:10px; font-size:9px; color:#475569;">
            <strong>Total de registros:</strong> {{ $clientes->count() }} cliente(s)
        </div>
    @else
        <div class="no-data">
            <p>No se encontraron clientes con los filtros aplicados.</p>
        </div>
    @endif

    <div class="footer">
        <p>{{ config('app.name', 'Sistema') }} - {{ now()->format('Y') }}</p>
    </div>
</body>
</html>
