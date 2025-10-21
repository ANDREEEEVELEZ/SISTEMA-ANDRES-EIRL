<?php

namespace App\Filament\Resources\Clientes\Pages;

use App\Filament\Resources\Clientes\ClienteResource;
use App\Filament\Resources\Clientes\Widgets\EstadisticasClientesWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClientes extends ListRecords
{
    protected static string $resource = ClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Registrar Cliente'),

            \Filament\Actions\Action::make('exportarClientes')
                ->label('Exportar clientes')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->modalHeading('Exportar clientes a PDF')
                ->modalSubmitActionLabel('Descargar PDF')
                ->form([
                   /* \Filament\Forms\Components\Select::make('tipo_cliente')
                        ->label('Tipo de Cliente')
                        ->options([
                            'natural' => 'Persona Natural',
                            'natural_con_negocio' => 'Natural con Negocio',
                            'juridica' => 'Persona Jurídica',
                        ])
                        ->placeholder('Todos'),*/

                    \Filament\Forms\Components\Select::make('tipo_doc')
                        ->label('Tipo de Documento')
                        ->options([
                            'DNI' => 'DNI',
                            'RUC' => 'RUC',
                        ])
                        ->placeholder('Todos'),
                    \Filament\Forms\Components\DatePicker::make('fecha_desde')
                        ->label('Fecha desde'),
                    \Filament\Forms\Components\DatePicker::make('fecha_hasta')
                        ->label('Fecha hasta'),

                          \Filament\Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options([
                            'activo' => 'Activo',
                            'inactivo' => 'Inactivo',
                        ])
                        ->placeholder('Todos'),

                ])
                ->action(function (array $data) {
                    $params = http_build_query(array_filter($data));
                    $url = route('clientes.exportar.pdf') . ($params ? ('?' . $params) : '');

                    // Redirigir a la URL para descargar el PDF
                    return redirect()->to($url);
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EstadisticasClientesWidget::class,
        ];
    }
}
