<?php

namespace App\Filament\Resources\ProduccionDiarias\Pages;

use App\Filament\Resources\ProduccionDiarias\ProduccionDiariaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProduccionDiarias extends ListRecords
{
    protected static string $resource = ProduccionDiariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
