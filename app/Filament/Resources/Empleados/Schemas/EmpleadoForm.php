<?php

namespace App\Filament\Resources\Empleados\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EmpleadoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('nombres')
                    ->required(),
                TextInput::make('apellidos')
                    ->required(),
                TextInput::make('dni')
                    ->required(),
                TextInput::make('telefono')
                    ->tel(),
                TextInput::make('direccion'),
                DatePicker::make('fecha_nacimiento')
                    ->required(),
                TextInput::make('correo_empleado'),
                TextInput::make('distrito'),
                DatePicker::make('fecha_incorporacion')
                    ->required(),
                TextInput::make('estado_empleado')
                    ->required(),
            ]);
    }
}
