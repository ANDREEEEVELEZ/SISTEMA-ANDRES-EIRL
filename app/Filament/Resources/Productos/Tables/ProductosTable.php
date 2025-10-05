<?php

namespace App\Filament\Resources\Productos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre_producto')
                    ->label('Producto')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                TextColumn::make('categoria.NombreCategoria')
                    ->label('Categoría')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info'),
                
                TextColumn::make('unidad_medida')
                    ->label('Unidad')
                    ->searchable(),
                
                TextColumn::make('stock_total')
                    ->label('Stock Actual')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn (string $state, $record): string => 
                        (int)$state <= $record->stock_minimo ? 'danger' : 'success'
                    ),
                
                TextColumn::make('stock_minimo')
                    ->label('Stock Mínimo')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),
                
                TextColumn::make('preciosProductos')
                    ->label('Precios')
                    ->formatStateUsing(function ($record) {
                        $precios = $record->preciosProductos()->orderBy('cantidad_minima', 'asc')->get();
                        if ($precios->isEmpty()) {
                            return 'Sin precio';
                        }
                        if ($precios->count() === 1) {
                            return 'S/ ' . number_format($precios->first()->precio_unitario, 2);
                        }
                        $precioMin = $precios->first()->precio_unitario;
                        $precioMax = $precios->last()->precio_unitario;
                        return 'S/ ' . number_format($precioMin, 2) . ' - S/ ' . number_format($precioMax, 2);
                    })
                    ->badge()
                    ->color('warning')
                    ->tooltip(function ($record) {
                        $precios = $record->preciosProductos()->orderBy('cantidad_minima', 'asc')->get();
                        if ($precios->isEmpty()) {
                            return 'No hay precios configurados';
                        }
                        return $precios->map(function ($precio) {
                            return "Desde {$precio->cantidad_minima} unidades: S/ " . number_format($precio->precio_unitario, 2);
                        })->join("\n");
                    }),
                
                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'success' => 'activo',
                        'danger' => 'inactivo',
                    ])
                    ->sortable(),
                
                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->searchable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('categoria_id')
                    ->label('Categoría')
                    ->relationship('categoria', 'NombreCategoria')
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'activo' => 'Activo',
                        'inactivo' => 'Inactivo',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('nombre_producto', 'asc');
    }
}
