<?php

namespace App\Filament\Resources\Productos\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('categoria_id')
                    ->relationship('categoria', 'id')
                    ->required(),
                TextInput::make('nombre_producto')
                    ->required(),
                TextInput::make('stock_total')
                    ->required()
                    ->numeric(),
                TextInput::make('descripcion'),
                TextInput::make('unidad_medida')
                    ->required(),
                Select::make('estado')
                    ->options(['activo' => 'Activo', 'inactivo' => 'Inactivo'])
                    ->required(),
                TextInput::make('stock_minimo')
                    ->required()
                    ->numeric(),
            ]);
    }
}
