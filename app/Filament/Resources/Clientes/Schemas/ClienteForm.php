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
                    ->label('Tipo de Documento')
                    ->options(['DNI' => 'DNI', 'RUC' => 'RUC'])
                    ->live() // Hace que el campo sea reactivo
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Si selecciona DNI, tipo_cliente es automáticamente 'natural'
                        if ($state === 'DNI') {
                            $set('tipo_cliente', 'natural');
                        } else {
                            // Si cambia a RUC, limpiar el tipo_cliente para que seleccione
                            $set('tipo_cliente', null);
                        }
                    })
                    ->required(),

                Select::make('tipo_cliente')
                    ->label('Tipo de Cliente')
                    ->options([
                        'natural' => '👤 Persona Natural',
                        'natural_con_negocio' => '🏪 Persona Natural con Negocio (RUC 10)',
                        'juridica' => '🏢 Persona Jurídica/Empresa (RUC 20)',
                    ])
                    ->visible(fn (callable $get) => $get('tipo_doc') === 'RUC') // Solo visible con RUC
                    ->disabled(fn (callable $get) => $get('tipo_doc') === 'DNI') // Deshabilitado con DNI
                    ->default(fn (callable $get) => $get('tipo_doc') === 'DNI' ? 'natural' : null)
                    ->required()
                    ->helperText(fn (callable $get) => 
                        $get('tipo_doc') === 'DNI' 
                            ? 'Con DNI siempre es Persona Natural' 
                            : 'Seleccione el tipo de cliente con RUC'
                    )
                    ->live(),

                TextInput::make('num_doc')
                    ->label('Número de Documento')
                    ->required()
                    ->live() // Hace que el campo sea reactivo
                    ->maxLength(fn (callable $get) => $get('tipo_doc') ? ($get('tipo_doc') === 'DNI' ? 8 : 11) : 11)
                    ->minLength(fn (callable $get) => $get('tipo_doc') ? ($get('tipo_doc') === 'DNI' ? 8 : 11) : 8)
                    ->length(fn (callable $get) => $get('tipo_doc') ? ($get('tipo_doc') === 'DNI' ? 8 : 11) : null)
                    ->placeholder(fn (callable $get) => $get('tipo_doc') ? ($get('tipo_doc') === 'DNI' ? 'Ingrese 8 dígitos' : 'Ingrese 11 dígitos') : 'Seleccione tipo de documento primero')
                    ->regex('/^[0-9]+$/')
                    ->validationMessages([
                        'regex' => 'El documento debe contener solo números.',
                    ]),
                    //->helperText(fn (callable $get) => $get('tipo_doc') ? ($get('tipo_doc') === 'DNI' ? 'DNI debe tener exactamente 8 dígitos' : 'RUC debe tener exactamente 11 dígitos') : 'Primero seleccione el tipo de documento'),

                TextInput::make('nombre_razon')
                ->label('Nombre o Razón Social')
                    ->required(),
                DatePicker::make('fecha_registro')
                    ->default(now()),

                Select::make('estado')
                    ->label('Estado')
                    ->options(['activo' => 'Activo', 'inactivo' => 'Inactivo'])
                    ->default('activo')
                    ->required()
                    ->disabled()
                    ->dehydrated(),

                TextInput::make('telefono')
                    ->tel()
                    ->numeric()
                    ->length(9),
                TextInput::make('direccion'),
            ]);
    }
}
