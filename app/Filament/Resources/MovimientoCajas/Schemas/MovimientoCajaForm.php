<?php

namespace App\Filament\Resources\MovimientoCajas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MovimientoCajaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('caja_id')
                    ->relationship('caja', 'id')
                    ->required(),
                Select::make('tipo')
                    ->options(['ingreso' => 'Ingreso', 'egreso' => 'Egreso'])
                    ->required(),
                TextInput::make('monto')
                    ->required()
                    ->numeric(),
                TextInput::make('descripcion')
                    ->required(),
                DatePicker::make('fecha_movimiento')
                    ->required(),
            ]);
    }
}
