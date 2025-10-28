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
                    ->required()
                    ->disabled(fn ($record) => $record && $record->estado === 'cerrada'),
                DateTimePicker::make('fecha_cierre')
                    ->default(fn ($record) => ($record && $record->estado === 'abierta' && request()->routeIs('filament.resources.cajas.edit')) ? now() : $record?->fecha_cierre)
                    ->disabled(fn ($record) => $record && $record->estado === 'cerrada'),
                TextInput::make('saldo_inicial')
                    ->required()
                    ->numeric()
                    ->disabled(fn ($record, $state, $operation) => $operation === 'edit' || ($record && $record->estado === 'cerrada'))
                    ->dehydrated()
                    ->helperText(fn (string $operation): string =>
                        $operation === 'edit'
                            ? 'El saldo inicial no puede ser modificado una vez creada la caja'
                            : ''
                    ),
                TextInput::make('saldo_final')
                    ->numeric()
                    ->disabled(fn ($record) => $record && $record->estado === 'cerrada'),
                Select::make('estado')
                    ->label('Estado')
                    ->options(['abierta' => 'Abierta', 'cerrada' => 'Cerrada'])
                    ->default('abierta')
                    ->required()
                    ->disabled(fn ($record, $state, $operation) => $operation === 'edit' || ($record && $record->estado === 'cerrada'))
                    ->dehydrated(),

                TextInput::make('observacion')
                    ->disabled(fn ($record) => $record && $record->estado === 'cerrada'),
            ]);
    }
}
