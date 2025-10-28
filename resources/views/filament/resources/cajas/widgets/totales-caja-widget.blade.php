<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
        <span class="text-lg font-semibold text-gray-700">Total de Ventas (Caja Abierta)</span>
        <span class="text-2xl font-bold text-green-600 mt-2">S/ {{ number_format($totalVentas, 2) }}</span>
    </div>
    <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
        <span class="text-lg font-semibold text-gray-700">Total de Ingresos (Caja Abierta)</span>
        <span class="text-2xl font-bold text-blue-600 mt-2">S/ {{ number_format($totalIngresos, 2) }}</span>
    </div>
    <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
        <span class="text-lg font-semibold text-gray-700">Total de Egresos (Caja Abierta)</span>
        <span class="text-2xl font-bold text-red-600 mt-2">S/ {{ number_format($totalEgresos, 2) }}</span>
    </div>
</div>
