<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\MovimientoCaja;
use Carbon\Carbon;

class IngresosEgresosDonutWidget extends ChartWidget
{
    protected static ?int $sort = 9;

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function getHeading(): ?string
    {
        return 'Distribución Ingresos vs Egresos (Mes Actual)';
    }

    protected function getData(): array
    {
        $inicio = Carbon::now()->startOfMonth();
        $fin = Carbon::now()->endOfMonth();

        $ingresos = MovimientoCaja::where('tipo', 'ingreso')
            ->whereBetween('created_at', [$inicio, $fin])
            ->sum('monto');

        $egresos = MovimientoCaja::where('tipo', 'egreso')
            ->whereBetween('created_at', [$inicio, $fin])
            ->sum('monto');

        $total = (float) $ingresos + (float) $egresos;

        // Si no hay movimientos, mostrar un segmento "Sin datos" para que el gráfico muestre tooltip
        if ($total === 0.0) {
            $labels = ['Sin datos'];
            $data = [1.0];
            $backgroundColor = ['rgba(156,163,175,0.6)']; // gray
            $borderColor = ['rgb(156,163,175)'];
        } else {
            $labels = ['Ingresos', 'Egresos'];
            $data = [(float) $ingresos, (float) $egresos];
            $backgroundColor = [
                'rgba(16,185,129,0.8)', // green
                'rgba(239,68,68,0.8)',  // red
            ];
            $borderColor = ['rgb(16,185,129)', 'rgb(239,68,68)'];
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $backgroundColor,
                    'borderColor' => $borderColor,
                    'borderWidth' => 1,
                    // Guardamos total para calcular porcentaje en el tooltip
                    'total' => $total,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
