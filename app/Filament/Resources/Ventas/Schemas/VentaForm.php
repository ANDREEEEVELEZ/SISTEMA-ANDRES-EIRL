<?php

namespace App\Filament\Resources\Ventas\Schemas;

use App\Models\Cliente;
use App\Models\Producto;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class VentaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // === INFORMACIÓN DEL USUARIO Y CAJA ===
                Select::make('user_id')
                    ->label('Vendedor')
                    ->relationship('user', 'name')
                    ->default(Auth::id())
                    ->required()
                    ->disabled()
                    ->dehydrated(),
                
                Select::make('caja_id')
                    ->label('Caja')
                    ->relationship('caja', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => 
                        "Caja #{$record->id} - " . 
                        $record->fecha_apertura->format('d/m/Y') . 
                        " ({$record->estado})"
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Seleccione la caja donde se registrará la venta'),
                
                // === INFORMACIÓN DEL CLIENTE ===
                Select::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'nombre_razon')
                    ->getOptionLabelFromRecordUsing(fn ($record) => 
                        "{$record->tipo_doc}: {$record->num_doc} - {$record->nombre_razon}"
                    )
                    ->searchable(['num_doc', 'nombre_razon'])
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Select::make('tipo_doc')
                            ->label('Tipo de Documento')
                            ->options([
                                'DNI' => 'DNI',
                                'RUC' => 'RUC',
                                'CE' => 'Carnet de Extranjería',
                                'PASAPORTE' => 'Pasaporte',
                            ])
                            ->required(),
                        
                        Select::make('tipo_cliente')
                            ->label('Tipo de Cliente')
                            ->options([
                                'natural' => 'Persona Natural',
                                'juridica' => 'Persona Jurídica',
                            ])
                            ->required(),
                        
                        TextInput::make('num_doc')
                            ->label('Número de Documento')
                            ->required()
                            ->maxLength(20),
                        
                        TextInput::make('nombre_razon')
                            ->label('Nombre / Razón Social')
                            ->required()
                            ->maxLength(255),
                        
                        TextInput::make('telefono')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(20),
                        
                        Textarea::make('direccion')
                            ->label('Dirección')
                            ->rows(2),
                    ])
                    ->createOptionModalHeading('Registrar Nuevo Cliente')
                    ->helperText('Busque por documento o nombre, o cree uno nuevo'),
                
                // === FECHA Y HORA DE LA VENTA ===
                DatePicker::make('fecha_venta')
                    ->label('Fecha de Venta')
                    ->default(now())
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                
                TimePicker::make('hora_venta')
                    ->label('Hora de Venta')
                    ->default(now()->format('H:i'))
                    ->required()
                    ->seconds(false),
                
                // === DETALLES DE LA VENTA (PRODUCTOS) ===
                Repeater::make('detalleVentas')
                    ->label('Productos de la Venta')
                    ->relationship('detalleVentas')
                    ->schema([
                        Select::make('producto_id')
                            ->label('Producto')
                            ->options(
                                Producto::where('estado', 'activo')
                                    ->where('stock_total', '>', 0)
                                    ->get()
                                    ->mapWithKeys(fn ($producto) => [
                                        $producto->id => "{$producto->nombre_producto} (Stock: {$producto->stock_total} {$producto->unidad_medida})"
                                    ])
                            )
                            ->searchable()
                            ->required()
                            ->helperText('Seleccione el producto a vender'),
                        
                        TextInput::make('cantidad_venta')
                            ->label('Cantidad')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->suffix('unidades'),
                        
                        TextInput::make('precio_unitario')
                            ->label('Precio Unitario')
                            ->required()
                            ->numeric()
                            ->prefix('S/')
                            ->step(0.01)
                            ->minValue(0.01)
                            ->helperText('Verifique el precio según la cantidad'),
                        
                        TextInput::make('descuento_unitario')
                            ->label('Descuento x Unidad')
                            ->numeric()
                            ->prefix('S/')
                            ->default(0)
                            ->minValue(0)
                            ->step(0.01),
                        
                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('S/')
                            ->required()
                            ->step(0.01)
                            ->helperText('(Precio - Descuento) x Cantidad'),
                    ])
                    ->columns(5)
                    ->defaultItems(1)
                    ->addActionLabel('➕ Agregar Producto')
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => 
                        isset($state['producto_id']) && isset($state['cantidad_venta'])
                            ? Producto::find($state['producto_id'])?->nombre_producto . " x{$state['cantidad_venta']}"
                            : 'Nuevo producto'
                    )
                    ->reorderable(false)
                    ->helperText('Agregue todos los productos que se están vendiendo. El sistema calculará los totales.')
                    ->columnSpanFull(),
                
                // === TOTALES DE LA VENTA ===
                TextInput::make('subtotal_venta')
                    ->label('Subtotal (Antes de Descuentos)')
                    ->numeric()
                    ->prefix('S/')
                    ->required()
                    ->step(0.01)
                    ->helperText('Suma de todos los productos sin descuentos'),
                
                TextInput::make('descuento_total')
                    ->label('Descuento Total Aplicado')
                    ->numeric()
                    ->prefix('S/')
                    ->default(0)
                    ->required()
                    ->step(0.01),
                
                TextInput::make('igv')
                    ->label('IGV (18%)')
                    ->numeric()
                    ->prefix('S/')
                    ->required()
                    ->step(0.01)
                    ->helperText('Calculado sobre base imponible'),
                
                TextInput::make('total_venta')
                    ->label('TOTAL A PAGAR')
                    ->numeric()
                    ->prefix('S/')
                    ->required()
                    ->step(0.01)
                    ->extraAttributes(['class' => 'font-bold text-lg'])
                    ->helperText('Monto total que debe pagar el cliente'),
                
                // === MÉTODO DE PAGO ===
                Select::make('metodo_pago')
                    ->label('Método de Pago')
                    ->options([
                        'efectivo' => 'Efectivo',
                        'tarjeta' => 'Tarjeta',
                        'yape' => 'Yape',
                        'plin' => 'Plin',
                        'transferencia' => 'Transferencia Bancaria',
                    ])
                    ->required()
                    ->helperText('Seleccione cómo pagará el cliente'),
                
                TextInput::make('cod_operacion')
                    ->label('Código de Operación / Transacción')
                    ->maxLength(100)
                    ->helperText('Solo para pagos digitales o con tarjeta'),
                
                // === ESTADO DE LA VENTA ===
                Select::make('estado_venta')
                    ->label('Estado de la Venta')
                    ->options([
                        'emitida' => 'Emitida',
                        'anulada' => 'Anulada',
                        'rechazada' => 'Rechazada'
                    ])
                    ->default('emitida')
                    ->required(),
            ]);
    }
}
