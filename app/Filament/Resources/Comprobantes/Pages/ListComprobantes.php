<?php

namespace App\Filament\Resources\Comprobantes\Pages;

use App\Filament\Resources\Comprobantes\ComprobanteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListComprobantes extends ListRecords
{
    protected static string $resource = ComprobanteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
