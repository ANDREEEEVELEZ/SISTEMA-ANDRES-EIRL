<?php

namespace App\Filament\Resources\Cajas\Pages;

use App\Filament\Resources\Cajas\CajaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCajas extends ListRecords
{
    protected static string $resource = CajaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
