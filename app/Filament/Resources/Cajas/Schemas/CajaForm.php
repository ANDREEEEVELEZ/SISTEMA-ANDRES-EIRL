<?php

namespace App\Filament\Resources\Cajas\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Get;

use Illuminate\Support\Facades\Auth;

class CajaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Usuario')
                    ->required()
                    ->default(fn () => Auth::id())
                    ->disabled(),
                DateTimePicker::make('fecha_apertura')
                    ->label('Fecha de Apertura')
                    ->required()
                    ->disabled(),
                DateTimePicker::make('fecha_cierre')
                    ->label('Fecha de Cierre')
                    ->default(fn ($record) => ($record && $record->estado === 'abierta' && request()->routeIs('filament.resources.cajas.edit')) ? now() : $record?->fecha_cierre)
                    ->disabled(),
                TextInput::make('saldo_inicial')
                    ->label('Saldo Inicial')
                    ->required()
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('saldo_final')
                    ->label('Saldo Final')
                    ->numeric()
                    ->disabled(),
                Select::make('estado')
                    ->label('Estado')
                    ->options(['abierta' => 'Abierta', 'cerrada' => 'Cerrada'])
                    ->default('abierta')
                    ->required()
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('observacion')
                    ->label('Observación')
                    ->disabled(),
            ]);
    }
}
