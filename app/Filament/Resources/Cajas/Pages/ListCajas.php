<?php

namespace App\Filament\Resources\Cajas\Pages;

use App\Filament\Resources\Cajas\CajaResource;
use App\Filament\Resources\Cajas\Widgets\AperturaCierreWidget;
use App\Filament\Resources\Cajas\Widgets\MovimientosCajaTable;
use Filament\Resources\Pages\ListRecords;


class ListCajas extends ListRecords
{
    protected static string $resource = CajaResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            AperturaCierreWidget::class,
            MovimientosCajaTable::class,
        ];
    }
    protected static int $headerWidgetsColumns = 2;
}

