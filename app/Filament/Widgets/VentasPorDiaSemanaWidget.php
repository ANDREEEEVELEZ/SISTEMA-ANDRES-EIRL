<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VentasPorDiaSemanaWidget extends ChartWidget
{
    protected static ?int $sort = 9;

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function getHeading(): ?string
    {
        return 'Ventas por Día de la Semana (Mes Actual)';
    }

    protected function getData(): array
    {
        $inicio = Carbon::now()->startOfMonth()->toDateString();
        $fin = Carbon::now()->endOfMonth()->toDateString();

        // MySQL DAYOFWEEK: 1=Sunday, 2=Monday, ..., 7=Saturday
        $rows = DB::table('ventas')
            ->select(DB::raw('DAYOFWEEK(fecha_venta) as dow'), DB::raw('COALESCE(SUM(total_venta), 0) as total'))
            ->whereBetween('fecha_venta', [$inicio, $fin])
            ->where('estado_venta', '!=', 'anulada')
            ->groupBy(DB::raw('DAYOFWEEK(fecha_venta)'))
            ->get()
            ->keyBy('dow');

        // Desired order: Lunes..Domingo
        $dowOrder = [2,3,4,5,6,7,1];
        $labels = [];
        $data = [];

        $spanishWeek = [
            1 => 'Domingo',
            2 => 'Lunes',
            3 => 'Martes',
            4 => 'Miércoles',
            5 => 'Jueves',
            6 => 'Viernes',
            7 => 'Sábado',
        ];

        foreach ($dowOrder as $d) {
            $labels[] = $spanishWeek[$d];
            $data[] = isset($rows[$d]) ? (float) $rows[$d]->total : 0.0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Ventas (S/)',
                    'data' => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.6)',
                    'borderColor' => 'rgb(59, 130, 246)',
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
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
