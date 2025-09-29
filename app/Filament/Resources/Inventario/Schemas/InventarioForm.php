<?php

namespace App\Filament\Resources\Inventario\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class InventarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('producto_id')
                    ->relationship('producto', 'nombre_producto')
                    ->searchable()
                    ->required()
                    ->label('Producto'),
                
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->label('Usuario'),
                
                Select::make('tipo')
                    ->options([
                        'entrada' => 'Entrada',
                        'salida' => 'Salida',
                        'ajuste' => 'Ajuste',
                    ])
                    ->required()
                    ->label('Tipo de Movimiento'),
                
                TextInput::make('cantidad_movimiento')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->label('Cantidad'),
                
                Textarea::make('motivo_movimiento')
                    ->required()
                    ->maxLength(255)
                    ->label('Motivo del Movimiento'),
                
                DatePicker::make('fecha_movimiento')
                    ->required()
                    ->default(now())
                    ->label('Fecha de Movimiento'),
            ]);
    }
}