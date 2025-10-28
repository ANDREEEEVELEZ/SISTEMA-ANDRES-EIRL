<?php

namespace App\Filament\Resources\Cajas\Pages;

use App\Filament\Resources\Cajas\CajaResource;
use App\Filament\Resources\Cajas\Widgets\AperturaCierreWidget;
use App\Filament\Resources\Cajas\Widgets\MovimientosCajaTable;
use App\Filament\Resources\Cajas\Widgets\TotalesCajaWidget;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;


class ListCajas extends ListRecords
{
    protected static string $resource = CajaResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TotalesCajaWidget::class,
            AperturaCierreWidget::class,
            MovimientosCajaTable::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('registrarMovimiento')
                ->label('Registrar movimiento')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(CajaResource::getUrl('registrar-movimiento')),

            Action::make('reporteArqueo')
                ->label('Reportes de arqueo')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->url(CajaResource::getUrl('arqueos')),
        ];
    }
    protected static int $headerWidgetsColumns = 2;
}

