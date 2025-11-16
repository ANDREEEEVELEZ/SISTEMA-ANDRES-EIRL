<?php

namespace App\Filament\Resources\Inventario\Pages;

use App\Filament\Resources\Inventario\InventarioResource;
use Filament\Resources\Pages\ViewRecord;

class ViewInventario extends ViewRecord
{
    protected static string $resource = InventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Solo visualización, sin acciones de edición o eliminación
        ];
    }
}