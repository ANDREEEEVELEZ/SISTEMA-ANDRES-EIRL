<?php

namespace App\Filament\Resources\Comprobantes\Pages;

use App\Filament\Resources\Comprobantes\ComprobanteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditComprobante extends EditRecord
{
    protected static string $resource = ComprobanteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
