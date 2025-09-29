<?php

namespace App\Filament\Resources\ProduccionDiarias\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProduccionDiariaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('producto_id')
                    ->relationship('producto', 'id')
                    ->required(),
                TextInput::make('cantidad_diaria')
                    ->required()
                    ->numeric(),
                DatePicker::make('fecha_produccion')
                    ->required(),
            ]);
    }
}
