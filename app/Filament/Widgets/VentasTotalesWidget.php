<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VentasTotalesWidget extends ChartWidget
{
    protected static ?int $sort = 9; // Justo antes de la tabla de stock crítico
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function getHeading(): ?string
    {
        return 'Ventas Últimos 7 Días';
    }

    protected function getData(): array
    {
        // Obtener datos de ventas de los últimos 7 días
        $ventas = DB::table('ventas')
            ->select(
                DB::raw('DATE(fecha_venta) as fecha'),
                DB::raw('COALESCE(SUM(total_venta), 0) as total')
            )
            ->where('fecha_venta', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->where('fecha_venta', '<=', Carbon::now()->endOfDay())
            ->where('estado_venta', '!=', 'anulada')
            ->groupBy(DB::raw('DATE(fecha_venta)'))
            ->orderBy('fecha')
            ->get();

        // Crear array con todos los días de la semana (últimos 7 días)
        $labels = [];
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $fecha = Carbon::now()->subDays($i);
            $labels[] = $fecha->format('d/m');

            // Buscar si hay ventas para esta fecha
            $ventaDelDia = $ventas->firstWhere('fecha', $fecha->format('Y-m-d'));
            $data[] = $ventaDelDia ? (float) $ventaDelDia->total : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Ventas (S/)',
                    'data' => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
