<?php

namespace App\Filament\Resources\SerieComprobantes\Pages;

use App\Filament\Resources\SerieComprobantes\SerieComprobanteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSerieComprobantes extends ListRecords
{
    protected static string $resource = SerieComprobanteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
