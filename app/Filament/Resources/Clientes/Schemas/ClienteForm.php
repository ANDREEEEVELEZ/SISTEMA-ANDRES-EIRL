<?php

namespace App\Filament\Resources\Clientes\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClienteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tipo_doc')
                    ->options(['DNI' => 'D n i', 'RUC' => 'R u c'])
                    ->required(),
                Select::make('tipo_cliente')
                    ->options(['natural' => 'Natural', 'juridica' => 'Juridica', 'otro' => 'Otro'])
                    ->required(),
                TextInput::make('num_doc')
                    ->required(),
                TextInput::make('nombre_razon')
                    ->required(),
                DatePicker::make('fecha_registro')
                    ->required(),
                Select::make('estado')
                    ->options(['activo' => 'Activo', 'inactivo' => 'Inactivo'])
                    ->required(),
                TextInput::make('telefono')
                    ->tel(),
                TextInput::make('direccion'),
            ]);
    }
}
