<?php

namespace App\Filament\Resources\Cajas\Pages;

use App\Filament\Resources\Cajas\CajaResource;
use App\Models\Caja;
use App\Services\CajaService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCajas extends ListRecords
{
    protected static string $resource = CajaResource::class;

    protected function getHeaderActions(): array
    {
        $cajaAnterior = CajaService::getCajaAbiertaDiaAnterior();
        $cajaHoy = CajaService::tieneCajaAbiertaHoy();

        $tooltip = null;
        $disabled = false;

        if ($cajaAnterior) {
            $tooltip = "URGENTE: Hay una caja del día anterior sin cerrar desde el {$cajaAnterior->fecha_apertura->format('d/m/Y H:i')}. Debe cerrarla MANUALMENTE primero. El sistema NO la cerrará automáticamente.";
            $disabled = true;
        } elseif ($cajaHoy) {
            $tooltip = "Ya existe una caja abierta hoy. Solo se permite una caja por día.";
            $disabled = true;
        }

        return [
            CreateAction::make()
                ->disabled($disabled)
                ->tooltip($tooltip)
                ->color($cajaAnterior ? 'danger' : 'primary'),
        ];
    }
}
