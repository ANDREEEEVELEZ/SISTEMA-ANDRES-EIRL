<?php

namespace App\Filament\Resources\ProduccionDiarias\Pages;

use App\Filament\Resources\ProduccionDiarias\ProduccionDiariaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduccionDiaria extends EditRecord
{
    protected static string $resource = ProduccionDiariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
