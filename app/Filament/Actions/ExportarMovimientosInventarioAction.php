<?php

namespace App\Filament\Actions;

use App\Models\Producto;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class ExportarMovimientosInventarioAction
{
    public static function make(): Action
    {
        return Action::make('exportar_movimientos')
            ->label('Exportar Movimientos')
           ->icon('heroicon-o-arrow-down-tray')
            ->color('primary')
            ->form([
                DatePicker::make('fecha_inicio')
                    ->label('Fecha Inicio')
                    ->default(now()->startOfMonth())
                    ->required()
                    ->maxDate(now())
                    ->native(false)
                    ->displayFormat('d/m/Y'),

                DatePicker::make('fecha_fin')
                    ->label('Fecha Fin')
                    ->default(now())
                    ->required()
                    ->maxDate(now())
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->afterOrEqual('fecha_inicio'),

                Select::make('tipo')
                    ->label('Tipo de Movimiento')
                    ->options([
                        'todos' => 'Todos',
                        'entrada' => 'Entradas',
                        'salida' => 'Salidas',
                        'ajuste' => 'Ajustes',
                    ])
                    ->default('todos')
                    ->required(),

                Select::make('producto_id')
                    ->label('Filtrar por Producto')
                    ->options(Producto::orderBy('nombre_producto')->pluck('nombre_producto', 'id'))
                    ->searchable()
                    ->placeholder('Todos los productos'),
            ])
            ->action(function (array $data) {
                $params = http_build_query(array_filter($data));
                $url = route('reportes.inventario.movimientos') . ($params ? '?' . $params : '');

                Notification::make()
                    ->title('Generando reporte...')
                    ->body('El reporte de movimientos se abrirá en una nueva pestaña.')
                    ->success()
                    ->send();

                return redirect()->away($url);
            })
            ->modalWidth('md')
            ->modalHeading('Exportar Movimientos de Inventario a PDF')
            ->modalDescription('Selecciona el periodo y filtros para generar el reporte de movimientos.')
            ->modalSubmitActionLabel('Generar PDF');
    }
}
