<?php

namespace App\Filament\Resources\PrecioProductos\Pages;

use App\Filament\Resources\PrecioProductos\PrecioProductoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPrecioProducto extends EditRecord
{
    protected static string $resource = PrecioProductoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
