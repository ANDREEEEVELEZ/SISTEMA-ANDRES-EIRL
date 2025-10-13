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
                    ->label('Usuario')
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
                    ->label('Estado')
                    ->options(['abierta' => 'Abierta', 'cerrada' => 'Cerrada'])
                    ->default('abierta')
                    ->required()
                    ->disabled()
                    ->dehydrated(),

                TextInput::make('observacion'),
            ]);
    }
}
