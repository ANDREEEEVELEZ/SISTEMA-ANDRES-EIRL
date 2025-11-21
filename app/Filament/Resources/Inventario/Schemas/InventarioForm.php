<?php

namespace App\Filament\Resources\Inventario\Schemas;

use App\Models\Producto;
use App\Models\User;
use App\Models\MovimientoInventario;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Radio;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class InventarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('producto_id')
                    ->options(Producto::where('estado', 'activo')->pluck('nombre_producto', 'id'))
                    ->searchable()
                    ->required()
                    ->label('Producto'),
                
                // El user_id se asigna automáticamente en CreateInventario
                // No se muestra en el formulario por seguridad
                
                Select::make('tipo')
                    ->options(function ($livewire) {
                        $user = Auth::user();
                        
                        // En modo creación: verificar rol del usuario
                        if ($livewire instanceof \Filament\Resources\Pages\CreateRecord) {
                            // Solo super_admin puede registrar entrada y ajuste
                            if ($user && $user->hasRole('super_admin')) {
                                return [
                                    'entrada' => 'Entrada',
                                    'ajuste' => 'Ajuste',
                                ];
                            } else {
                                // Vendedores solo pueden hacer ajustes
                                return [
                                    'ajuste' => 'Ajuste',
                                ];
                            }
                        }
                        
                        // Para visualización, mostrar todos los tipos
                        return [
                            'entrada' => 'Entrada',
                            'salida' => 'Salida',
                            'ajuste' => 'Ajuste',
                        ];
                    })
                    ->helperText(function ($livewire) {
                        $user = Auth::user();
                        
                        if ($livewire instanceof \Filament\Resources\Pages\CreateRecord) {
                            if ($user && $user->hasRole('super_admin')) {
                                return 'Las salidas se registran automáticamente desde las ventas';
                            } else {
                                return 'Como vendedor, solo puede realizar ajustes de inventario';
                            }
                        }
                        return null;
                    })
                    ->required()
                    ->default(function ($livewire) {
                        $user = Auth::user();
                        if ($livewire instanceof CreateRecord) {
                            if ($user && $user->hasRole('super_admin')) {
                                return null;
                            }
                            return 'ajuste';
                        }
                        return null;
                    })
                    ->disabled(function ($livewire) {
                        $user = Auth::user();
                        return ($livewire instanceof CreateRecord) && (! $user || ! $user->hasRole('super_admin'));
                    })
                    ->live()
                    ->label('Tipo de Movimiento'),
                
                // Campos específicos para ajustes
                Radio::make('metodo_ajuste')
                    ->options([
                        'absoluto' => 'Establecer stock en cantidad exacta',
                        'relativo' => 'Ajustar stock por diferencia (+/-)',
                    ])
                    ->default('relativo')
                    ->descriptions([
                        'absoluto' => 'El stock se establecerá exactamente en la cantidad indicada',
                        'relativo' => 'La cantidad se sumará o restará del stock actual (usa números negativos para restar)',
                    ])
                    ->visible(fn ($get) => $get('tipo') === 'ajuste')
                    ->required(fn ($get) => $get('tipo') === 'ajuste')
                    ->label('Método de Ajuste'),
                
                Select::make('motivo_ajuste')
                    ->options([
                        'conteo_fisico' => 'Conteo Físico (Diferencia en inventario)',
                        'vencido' => 'Productos Vencidos',
                        'danado' => 'Productos Dañados',
                        'robo' => 'Robo o Pérdida',
                        'otro' => 'Otro',
                    ])
                    ->visible(fn ($get) => $get('tipo') === 'ajuste')
                    ->required(fn ($get) => $get('tipo') === 'ajuste')
                    ->live()
                    ->label('Motivo del Ajuste'),
                
                TextInput::make('cantidad_movimiento')
                    ->required()
                    ->numeric()
                    ->minValue(function ($get) {
                        // Si es ajuste relativo, permitir números negativos
                        if ($get('tipo') === 'ajuste' && $get('metodo_ajuste') === 'relativo') {
                            return null; // Sin límite mínimo
                        }
                        return 1;
                    })
                    ->suffix(function ($get) {
                        if ($get('tipo') === 'ajuste' && $get('metodo_ajuste') === 'relativo') {
                            return 'unidades (+/-)';
                        }
                        return 'unidades';
                    })
                    ->helperText(function ($get) {
                        if ($get('tipo') === 'ajuste' && $get('metodo_ajuste') === 'relativo') {
                            return 'Usa números negativos para restar (ej: -10 para quitar 10 unidades)';
                        }
                        return null;
                    })
                    ->label('Cantidad'),
                
                Textarea::make('motivo_movimiento')
                    ->visible(fn ($get) => 
                        $get('tipo') === 'entrada' || 
                        ($get('tipo') === 'ajuste' && $get('motivo_ajuste') === 'otro')
                    )
                    ->required(fn ($get) => 
                        $get('tipo') === 'entrada' || 
                        ($get('tipo') === 'ajuste' && $get('motivo_ajuste') === 'otro')
                    )
                    ->maxLength(255)
                    ->label(fn ($get) => $get('tipo') === 'entrada' ? 'Motivo de la Entrada' : 'Motivo del Ajuste (Especificar)')
                    ->placeholder(fn ($get) => $get('tipo') === 'entrada' ? 'Ej: Compra de mercadería, Devolución de cliente, etc.' : 'Ingrese el motivo específico del ajuste...')
                    ->helperText(fn ($get) => $get('tipo') === 'entrada' ? 'Describa el motivo de la entrada de productos' : 'Campo obligatorio cuando selecciona "Otro" como motivo'),
                
                DatePicker::make('fecha_movimiento')
                    ->required()
                    ->default(now())
                    ->label('Fecha de Movimiento'),
            ]);
    }
}