<?php

namespace App\Filament\Resources\Productos\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;

class ProductoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // === INFORMACIÓN BÁSICA DEL PRODUCTO ===
                Select::make('categoria_id')
                    ->label('Categoría')
                    ->relationship('categoria', 'NombreCategoria')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('NombreCategoria')
                            ->label('Nombre de la Categoría')
                            ->required()
                    ]),
                
                TextInput::make('nombre_producto')
                    ->label('Nombre del Producto')
                    ->required()
                    ->maxLength(255),
                
                Textarea::make('descripcion')
                    ->label('Descripción')
                    ->rows(3)
                    ->columnSpanFull(),
                
                TextInput::make('unidad_medida')
                    ->label('Unidad de Medida')
                    ->required()
                    ->placeholder('Ej: Unidad, Kg, Litro, Caja, etc.')
                    ->maxLength(50)
                    ->helperText('Especifique cómo se mide este producto'),
                
                Select::make('estado')
                    ->label('Estado')
                    ->options([
                        'activo' => 'Activo',
                        'inactivo' => 'Inactivo'
                    ])
                    ->default('activo')
                    ->required(),
                
                // === CONTROL DE INVENTARIO ===
                TextInput::make('stock_total')
                    ->label('Stock Total')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->suffix('unidades')
                    ->helperText('Cantidad actual en inventario'),
                
                TextInput::make('stock_minimo')
                    ->label('Stock Mínimo')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->suffix('unidades')
                    ->helperText('Cantidad mínima para generar alertas de reposición'),
                
                // === TABLA DE PRECIOS ===
                Repeater::make('preciosProductos')
                    ->label('Precios del Producto')
                    ->relationship('preciosProductos')
                    ->schema([
                        TextInput::make('cantidad_minima')
                            ->label('Cantidad Mínima')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->suffix('unidades')
                            ->helperText('A partir de cuántas unidades aplica este precio'),
                        
                        TextInput::make('precio_unitario')
                            ->label('Precio Unitario')
                            ->required()
                            ->numeric()
                            ->prefix('S/')
                            ->minValue(0.01)
                            ->step(0.01)
                            ->helperText('Precio por cada unidad'),
                    ])
                    ->columns(2)
                    ->defaultItems(1)
                    ->addActionLabel('Agregar Otro Precio')
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => 
                        isset($state['cantidad_minima']) && isset($state['precio_unitario'])
                            ? "Desde {$state['cantidad_minima']} unidades → S/ {$state['precio_unitario']} c/u"
                            : 'Nuevo precio'
                    )
                    ->reorderable()
                    ->cloneable()
                    ->helperText('Configure precios escalonados. Por ejemplo: 1-10 unidades a S/5.00, 11+ unidades a S/4.50')
                    ->columnSpanFull(),
            ]);
    }
}
