<?php

namespace App\Filament\Resources\PrecioProductos\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PrecioProductoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('producto_id')
                    ->relationship('producto', 'id')
                    ->required(),
                TextInput::make('cantidad_minima')
                    ->required()
                    ->numeric(),
                TextInput::make('precio_unitario')
                    ->required()
                    ->numeric(),
            ]);
    }
}
