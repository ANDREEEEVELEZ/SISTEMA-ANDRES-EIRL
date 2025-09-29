<?php

namespace App\Filament\Resources\MovimientoInventarios\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MovimientoInventarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('producto_id')
                    ->relationship('producto', 'id')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('tipo')
                    ->options(['entrada' => 'Entrada', 'salida' => 'Salida', 'ajuste' => 'Ajuste'])
                    ->required(),
                TextInput::make('cantidad_movimiento')
                    ->required()
                    ->numeric(),
                TextInput::make('motivo_movimiento')
                    ->required(),
                DatePicker::make('fecha_movimiento')
                    ->required(),
            ]);
    }
}
