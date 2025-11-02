<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;

class ExportarReporteCompletoAction
{
    public static function make(): Action
    {
        return Action::make('exportar_completo')
            ->label('Reporte Completo')
            ->icon('heroicon-o-clipboard-document-list')
            ->color('warning')
            ->form([
                DatePicker::make('fecha_inicio')
                    ->label('Fecha Inicio (Movimientos)')
                    ->helperText('Periodo para filtrar los movimientos de inventario')
                    ->default(now()->startOfMonth())
                    ->required()
                    ->maxDate(now())
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                
                DatePicker::make('fecha_fin')
                    ->label('Fecha Fin (Movimientos)')
                    ->helperText('Los productos en stock se mostrarán al momento actual')
                    ->default(now())
                    ->required()
                    ->maxDate(now())
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->afterOrEqual('fecha_inicio'),
            ])
            ->action(function (array $data) {
                $params = http_build_query(array_filter($data));
                $url = route('reportes.inventario.completo') . ($params ? '?' . $params : '');
                
                Notification::make()
                    ->title('Generando reporte completo...')
                    ->body('El reporte completo de inventario se abrirá en una nueva pestaña.')
                    ->success()
                    ->send();

                return redirect()->away($url);
            })
            ->modalWidth('md')
            ->modalHeading('Exportar Reporte Completo de Inventario')
            ->modalDescription('Genera un reporte que incluye productos en stock y movimientos del periodo seleccionado.')
            ->modalSubmitActionLabel('Generar PDF');
    }
}
