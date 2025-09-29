<?php

namespace App\Filament\Resources\Cajas\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CajaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                DateTimePicker::make('fecha_apertura')
                    ->required(),
                DateTimePicker::make('fecha_cierre'),
                TextInput::make('saldo_inicial')
                    ->required()
                    ->numeric(),
                TextInput::make('saldo_final')
                    ->numeric(),
                Select::make('estado')
                    ->options(['abierta' => 'Abierta', 'cerrada' => 'Cerrada'])
                    ->required(),
                TextInput::make('observacion'),
            ]);
    }
}
