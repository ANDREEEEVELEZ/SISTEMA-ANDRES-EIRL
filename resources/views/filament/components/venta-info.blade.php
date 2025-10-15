<div class="bg-gray-50 p-4 rounded-lg border">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <h4 class="font-semibold text-gray-900 mb-2">Información del Comprobante</h4>
            <div class="space-y-1 text-sm">
                <div><span class="font-medium">Tipo:</span> {{ strtoupper($comprobante?->tipo ?? 'N/A') }}</div>
                <div><span class="font-medium">Serie-Número:</span> {{ $comprobante?->serie ?? 'N/A' }}-{{ $comprobante?->correlativo ?? 'N/A' }}</div>
                <div><span class="font-medium">Fecha:</span> {{ $venta->fecha_venta->format('d/m/Y') }}</div>
                <div><span class="font-medium">Estado:</span>
                    <span class="px-2 py-1 rounded text-xs {{ $venta->estado_venta === 'emitida' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ strtoupper($venta->estado_venta) }}
                    </span>
                </div>
            </div>
        </div>

        <div>
            <h4 class="font-semibold text-gray-900 mb-2">Información del Cliente</h4>
            <div class="space-y-1 text-sm">
                <div><span class="font-medium">Cliente:</span> {{ $venta->cliente->nombre_razon }}</div>
                <div><span class="font-medium">Documento:</span> {{ $venta->cliente->num_doc }}</div>
                <div><span class="font-medium">Total:</span> S/. {{ number_format($venta->total_venta, 2) }}</div>
            </div>
        </div>
    </div>

    @if($venta->detalleVentas->count() > 0)
    <div class="mt-4">
        <h4 class="font-semibold text-gray-900 mb-2">Productos</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-2 py-1 text-left">Producto</th>
                        <th class="px-2 py-1 text-right">Cant.</th>
                        <th class="px-2 py-1 text-right">P. Unit.</th>
                        <th class="px-2 py-1 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($venta->detalleVentas as $detalle)
                    <tr class="border-b">
                        <td class="px-2 py-1">{{ $detalle->producto->nombre_producto ?? 'N/A' }}</td>
                        <td class="px-2 py-1 text-right">{{ $detalle->cantidad_venta }}</td>
                        <td class="px-2 py-1 text-right">S/. {{ number_format($detalle->precio_venta, 2) }}</td>
                        <td class="px-2 py-1 text-right">S/. {{ number_format($detalle->subtotal_detalle, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
