<?php

namespace App\Filament\Resources\PrecioProductos\Pages;

use App\Filament\Resources\PrecioProductos\PrecioProductoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrecioProductos extends ListRecords
{
    protected static string $resource = PrecioProductoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
