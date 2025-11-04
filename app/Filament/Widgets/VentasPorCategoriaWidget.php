<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VentasPorCategoriaWidget extends ChartWidget
{
    protected static ?int $sort = 9; // Alineado con VentasTotalesWidget

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function getHeading(): ?string
    {
        return 'Ventas por Producto (Top 5 - Mes Actual)';
    }

    protected function getData(): array
    {
        $inicio = Carbon::now()->startOfMonth()->toDateString();
        $fin = Carbon::now()->endOfMonth()->toDateString();

        // Obtener monto vendido por producto para el mes actual (Top N)
        $topN = 5; // puedes cambiar a 10 si prefieres Top 10

        $rows = DB::table('detalle_ventas')
            ->select(
                'productos.id as producto_id',
                'productos.nombre_producto as producto',
                DB::raw('COALESCE(SUM(detalle_ventas.cantidad_venta * detalle_ventas.precio_unitario), 0) as monto_vendido')
            )
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->whereBetween('ventas.fecha_venta', [$inicio, $fin])
            ->where('ventas.estado_venta', '!=', 'anulada')
            ->groupBy('productos.id', 'productos.nombre_producto')
            ->orderByDesc('monto_vendido')
            ->limit($topN)
            ->get();

        $labels = $rows->map(fn($r) => $r->producto ?? 'Sin nombre')->toArray();
        $data = $rows->map(fn($r) => (float) $r->monto_vendido)->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Monto vendido (S/)',
                    'data' => $data,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.6)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "S/ " + value.toFixed(2); }',
                    ],
                ],
                'x' => [
                    'display' => true,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
