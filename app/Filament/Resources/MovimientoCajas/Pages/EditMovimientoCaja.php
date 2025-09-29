<?php

namespace App\Filament\Resources\MovimientoCajas\Pages;

use App\Filament\Resources\MovimientoCajas\MovimientoCajaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMovimientoCaja extends EditRecord
{
    protected static string $resource = MovimientoCajaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
