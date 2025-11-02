<?php

namespace App\Filament\Actions;

use App\Models\Categoria;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

class ExportarInventarioStockAction
{
    public static function make(): Action
    {
        return Action::make('exportar_stock')
            ->label('Exportar Stock')
            ->icon('heroicon-o-document-arrow-down')
            ->color('success')
            ->form([
                Select::make('categoria_id')
                    ->label('Filtrar por Categoría')
                    ->options(Categoria::pluck('NombreCategoria', 'id'))
                    ->searchable()
                    ->placeholder('Todas las categorías'),
                
                Select::make('estado_stock')
                    ->label('Filtrar por Estado de Stock')
                    ->options([
                        'todos' => 'Todos los productos',
                        'agotados' => 'Solo agotados',
                        'bajo' => 'Solo stock bajo',
                        'normal' => 'Solo stock normal',
                    ])
                    ->default('todos')
                    ->required(),
            ])
            ->action(function (array $data) {
                $params = http_build_query(array_filter($data));
                $url = route('reportes.inventario.stock') . ($params ? '?' . $params : '');
                
                Notification::make()
                    ->title('Generando reporte...')
                    ->body('El reporte de stock se abrirá en una nueva pestaña.')
                    ->success()
                    ->send();

                return redirect()->away($url);
            })
            ->modalWidth('md')
            ->modalHeading('Exportar Inventario de Stock a PDF')
            ->modalDescription('Selecciona los filtros para generar el reporte de productos en stock.')
            ->modalSubmitActionLabel('Generar PDF');
    }
}
