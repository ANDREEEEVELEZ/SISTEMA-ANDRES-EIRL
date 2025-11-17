<?php

namespace App\Filament\Resources\Productos\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->selectCurrentPageOnly()
            ->deselectAllRecordsWhenFiltered(false)
            ->checkIfRecordIsSelectableUsing(fn (): bool => false)
            ->columns([
                TextColumn::make('nombre_producto')
                    ->label('Producto')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon(fn ($record) => match(true) {
                        $record->estaAgotado() => 'heroicon-o-exclamation-circle',
                        $record->tieneStockBajo() => 'heroicon-o-exclamation-triangle',
                        default => null,
                    })
                    ->iconColor(fn ($record) => match(true) {
                        $record->estaAgotado() => 'danger',
                        $record->tieneStockBajo() => 'warning',
                        default => null,
                    }),
                
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
                    ->color(fn ($record): string => match(true) {
                        $record->estaAgotado() => 'danger',
                        $record->tieneStockBajo() => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->estaAgotado()) {
                            return '⚠️ ' . $state . ' (AGOTADO)';
                        }
                        if ($record->tieneStockBajo()) {
                            return '⚠️ ' . $state . ' (STOCK BAJO)';
                        }
                        return $state;
                    })
                    ->tooltip(function ($record) {
                        if ($record->estaAgotado()) {
                            return '🔴 PRODUCTO AGOTADO: Stock actual: ' . $record->stock_total . ' | Stock mínimo: ' . $record->stock_minimo . ' | Es necesario reponer urgentemente.';
                        }
                        if ($record->tieneStockBajo()) {
                            $faltante = $record->stock_minimo - $record->stock_total;
                            return '🟡 STOCK BAJO: Stock actual: ' . $record->stock_total . ' | Stock mínimo: ' . $record->stock_minimo . ' | Faltante: ' . $faltante . ' unidades.';
                        }
                        $excedente = $record->stock_total - $record->stock_minimo;
                        return '🟢 Stock normal: ' . $record->stock_total . ' unidades disponibles (' . $excedente . ' unidades por encima del mínimo).';
                    }),
                
                TextColumn::make('stock_minimo')
                    ->label('Stock Mínimo')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                
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
                    ->sortable(),
                
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
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
                
                Filter::make('estado_stock')
                    ->label('Estado de Stock')
                    ->form([
                        \Filament\Forms\Components\Select::make('tipo')
                            ->label('Filtrar por')
                            ->options([
                                'agotado' => 'Productos Agotados',
                                'bajo' => 'Stock Bajo',
                                'alerta' => 'Con Alerta (Agotados + Stock Bajo)',
                                'normal' => 'Stock Normal',
                            ])
                            ->placeholder('Seleccionar estado'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['tipo'] === 'agotado',
                                fn (Builder $query) => $query->agotados()
                            )
                            ->when(
                                $data['tipo'] === 'bajo',
                                fn (Builder $query) => $query->stockBajo()
                            )
                            ->when(
                                $data['tipo'] === 'alerta',
                                fn (Builder $query) => $query->conAlertaStock()
                            )
                            ->when(
                                $data['tipo'] === 'normal',
                                fn (Builder $query) => $query->whereRaw('stock_total > stock_minimo')
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!isset($data['tipo'])) {
                            return null;
                        }
                        
                        return match($data['tipo']) {
                            'agotado' => 'Productos Agotados',
                            'bajo' => 'Stock Bajo',
                            'alerta' => 'Con Alerta de Stock',
                            'normal' => 'Stock Normal',
                            default => null,
                        };
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            // No se permiten acciones de eliminación masiva por políticas de seguridad
            ->toolbarActions([])
            ->defaultSort('nombre_producto', 'asc');
    }
}
