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
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EstadisticasClientesWidget::class,
        ];
    }
}
