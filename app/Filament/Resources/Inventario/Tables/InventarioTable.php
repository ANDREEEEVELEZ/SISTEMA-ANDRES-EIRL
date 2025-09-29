<?php

namespace App\Filament\Resources\Inventario\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InventarioTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('producto.nombre_producto')
                    ->searchable()
                    ->sortable()
                    ->label('Producto'),
                
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Usuario'),
                
                TextColumn::make('tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'entrada' => 'success',
                        'salida' => 'danger',
                        'ajuste' => 'warning',
                        default => 'gray',
                    })
                    ->label('Tipo'),
                
                TextColumn::make('cantidad_movimiento')
                    ->numeric()
                    ->sortable()
                    ->label('Cantidad'),
                
                TextColumn::make('motivo_movimiento')
                    ->searchable()
                    ->limit(50)
                    ->label('Motivo'),
                
                TextColumn::make('fecha_movimiento')
                    ->date()
                    ->sortable()
                    ->label('Fecha'),
                
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Creado'),
                
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Actualizado'),
            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->options([
                        'entrada' => 'Entrada',
                        'salida' => 'Salida',
                        'ajuste' => 'Ajuste',
                    ])
                    ->label('Tipo de Movimiento'),
                
                SelectFilter::make('producto_id')
                    ->relationship('producto', 'nombre_producto')
                    ->searchable()
                    ->label('Producto'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('fecha_movimiento', 'desc');
    }
}