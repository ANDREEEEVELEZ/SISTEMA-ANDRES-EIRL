<?php

namespace App\Filament\Resources\Categorias\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoriaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('NombreCategoria')
                    ->label('Nombre de la Categoría')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
