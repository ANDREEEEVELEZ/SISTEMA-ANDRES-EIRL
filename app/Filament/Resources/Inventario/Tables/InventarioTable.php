<?php

namespace App\Filament\Resources\Inventario\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class InventarioTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->selectCurrentPageOnly()
            ->deselectAllRecordsWhenFiltered(false)
            ->checkIfRecordIsSelectableUsing(fn (): bool => false)
            ->columns([
                TextColumn::make('producto.nombre_producto')
                    ->searchable()
                    ->sortable()
                    ->label('Producto'),
                
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Usuario')
                    ->visible(fn () => Auth::user()?->hasRole('super_admin')),
                
                TextColumn::make('tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'entrada' => 'success',
                        'salida' => 'danger',
                        'ajuste' => 'warning',
                        default => 'gray',
                    })
                    ->label('Tipo'),
                
                // Columna 'metodo_ajuste' removida de la vista principal para simplificar la tabla.
                
                TextColumn::make('motivo_ajuste')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'conteo_fisico' => 'info',
                        'vencido' => 'warning',
                        'danado' => 'danger',
                        'robo' => 'danger',
                        'otro' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'conteo_fisico' => 'Conteo Físico',
                        'vencido' => 'Vencido',
                        'danado' => 'Dañado',
                        'robo' => 'Robo/Pérdida',
                        'otro' => 'Otro',
                        default => '-',
                    })
                    ->label('Motivo Ajuste'),
                
                TextColumn::make('cantidad_movimiento')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        // Si es ajuste relativo, mostrar el signo
                        if ($record && $record->tipo === 'ajuste' && $record->metodo_ajuste === 'relativo') {
                            $cantidad = $record->cantidad_movimiento;
                            return $cantidad > 0 ? "+{$cantidad}" : $cantidad;
                        }
                        return $record ? $record->cantidad_movimiento : '';
                    })
                    ->color(function ($record) {
                        if ($record && $record->tipo === 'ajuste' && $record->metodo_ajuste === 'relativo') {
                            return $record->cantidad_movimiento < 0 ? 'danger' : 'success';
                        }
                        return 'gray';
                    })
                    ->label('Cantidad'),
                
                TextColumn::make('motivo_movimiento')
                    ->searchable()
                    ->limit(50)
                    ->label('Motivo'),
                
                TextColumn::make('fecha_movimiento')
                    ->date()
                    ->sortable()
                    ->label('Fecha'),
                
                // 'created_at' y 'updated_at' removidos de la vista principal.
            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->options([
                        'entrada' => 'Entrada',
                        'salida' => 'Salida (Automática)',
                        'ajuste' => 'Ajuste',
                    ])
                    ->label('Tipo de Movimiento'),
                
                SelectFilter::make('motivo_ajuste')
                    ->options([
                        'conteo_fisico' => 'Conteo Físico',
                        'vencido' => 'Productos Vencidos',
                        'danado' => 'Productos Dañados',
                        'robo' => 'Robo/Pérdida',
                        'otro' => 'Otro',
                    ])
                    ->label('Motivo de Ajuste'),
                
                SelectFilter::make('producto_id')
                    ->relationship('producto', 'nombre_producto')
                    ->searchable()
                    ->label('Producto'),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make()
                    ->label('Ver Detalles'),
            ])
            // No se permiten acciones de eliminación masiva por políticas de seguridad
            ->toolbarActions([])
            ->defaultSort('fecha_movimiento', 'desc');
    }
}