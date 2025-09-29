<?php

namespace App\Filament\Resources\Inventario\Pages;

use App\Filament\Resources\Inventario\InventarioResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventario extends CreateRecord
{
    protected static string $resource = InventarioResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}