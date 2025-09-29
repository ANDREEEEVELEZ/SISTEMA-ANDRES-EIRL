<?php

namespace App\Filament\Resources\SerieComprobantes\Pages;

use App\Filament\Resources\SerieComprobantes\SerieComprobanteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSerieComprobante extends EditRecord
{
    protected static string $resource = SerieComprobanteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
