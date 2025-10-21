<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 11px;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        
        .export-info {
            text-align: right;
            font-size: 10px;
            color: #666;
            margin-bottom: 15px;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
        }
        
        th, td { 
            border: 1px solid #888; 
            padding: 6px 4px; 
            text-align: left;
            font-size: 10px;
        }
        
        th { 
            background: #f0f0f0; 
            font-weight: bold;
        }
        
        @page {
            margin: 100px 25px;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .footer {
            position: fixed;
            bottom: -60px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
        
        .pagenum:before {
            content: counter(page);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CLIENTES</h1>
    </div>
    
    <div class="export-info">
        Fecha de exportación: {{ now()->format('d/m/Y H:i:s') }}
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Tipo Doc</th>
                <th>N° Doc</th>
                <th>Nombre/Razón Social</th>
                <th>Tipo Cliente</th>
                <th>Estado</th>
                <th>Fecha Registro</th>
            </tr>
        </thead>
        <tbody>
        @forelse($clientes as $cliente)
            <tr>
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
                <td>{{ ucfirst($cliente->estado) }}</td>
                <td>{{ \Carbon\Carbon::parse($cliente->fecha_registro)->format('d/m/Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align: center;">No hay clientes con esos filtros.</td></tr>
        @endforelse
        </tbody>
    </table>
    
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
