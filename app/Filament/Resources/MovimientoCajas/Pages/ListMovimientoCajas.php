<?php

namespace App\Filament\Resources\MovimientoCajas\Pages;

use App\Filament\Resources\MovimientoCajas\MovimientoCajaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMovimientoCajas extends ListRecords
{
    protected static string $resource = MovimientoCajaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
