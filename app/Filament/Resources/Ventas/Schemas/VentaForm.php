<?php

namespace App\Filament\Resources\Ventas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class VentaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('cliente_id')
                    ->relationship('cliente', 'id')
                    ->required(),
                Select::make('caja_id')
                    ->relationship('caja', 'id')
                    ->required(),
                TextInput::make('subtotal_venta')
                    ->required()
                    ->numeric(),
                TextInput::make('igv')
                    ->required()
                    ->numeric(),
                TextInput::make('descuento_total')
                    ->required()
                    ->numeric(),
                TextInput::make('total_venta')
                    ->required()
                    ->numeric(),
                DatePicker::make('fecha_venta')
                    ->required(),
                TimePicker::make('hora_venta')
                    ->required(),
                Select::make('estado_venta')
                    ->options(['emitida' => 'Emitida', 'anulada' => 'Anulada', 'rechazada' => 'Rechazada'])
                    ->required(),
                TextInput::make('metodo_pago')
                    ->required(),
                TextInput::make('cod_operacion'),
            ]);
    }
}
