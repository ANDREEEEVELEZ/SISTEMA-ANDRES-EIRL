<?php

namespace App\Filament\Resources\Ventas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VentasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                                TextColumn::make('comprobantes')
                    ->label('Comprobante')
                    ->formatStateUsing(function ($record) {
                        $comprobante = $record->comprobantes->first();
                        if (!$comprobante) {
                            return 'Sin comprobante';
                        }
                        return "{$comprobante->tipo} {$comprobante->serie}-{$comprobante->correlativo}";
                    })
                    ->badge()
                    ->color(fn ($record) => $record->comprobantes->first() ? 'success' : 'danger'),


                TextColumn::make('fecha_venta')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

               /* TextColumn::make('hora_venta')
                    ->label('Hora')
                    ->time('H:i')
                    ->sortable()
                    ->toggleable(),*/

                TextColumn::make('cliente.nombre_razon')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),

                TextColumn::make('cliente.num_doc')
                    ->label('Doc. Cliente')
                    ->searchable(),
                    //->toggleable(),

                /*TextColumn::make('user.name')
                    ->label('Vendedor')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),*/

               /* TextColumn::make('caja.id')
                    ->label('Caja')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => "Caja #{$state}")
                    ->toggleable(),*/

              /*  TextColumn::make('detalleVentas')
                    ->label('Productos')
                    ->formatStateUsing(function ($record) {
                        $cantidad = $record->detalleVentas->count();
                        if ($cantidad === 0) {
                            return 'Sin productos';
                        }
                        if ($cantidad === 1) {
                            return '1 producto';
                        }
                        return "{$cantidad} productos";
                    })
                    ->badge()
                    ->color('info')
                    ->tooltip(function ($record) {
                        $productos = $record->detalleVentas;
                        if ($productos->isEmpty()) {
                            return 'No hay productos registrados';
                        }
                        return $productos->map(function ($detalle) {
                            return "• {$detalle->producto->nombre_producto} x{$detalle->cantidad_venta} = S/ " .
                            number_format($detalle->subtotal, 2);
                        })->join("\n");
                    }),

                TextColumn::make('subtotal_venta')
                    ->label('Subtotal')
                    ->money('PEN')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('descuento_total')
                    ->label('Descuento')
                    ->money('PEN')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('igv')
                    ->label('IGV')
                    ->money('PEN')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),*/

                TextColumn::make('total_venta')
                    ->label('Total')
                    ->money('PEN')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color('success'),

                BadgeColumn::make('metodo_pago')
                    ->label('Método Pago')
                    ->colors([
                        'success' => 'efectivo',
                        'primary' => 'tarjeta',
                        'warning' => 'yape',
                        'info' => 'plin',
                        'secondary' => 'transferencia',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'efectivo' => 'Efectivo',
                        'tarjeta' => 'Tarjeta',
                        'yape' => 'Yape',
                        'plin' => 'Plin',
                        'transferencia' => ' Transferencia',
                        default => $state,
                    })
                    ->sortable(),

                BadgeColumn::make('estado_venta')
                    ->label('Estado')
                    ->colors([
                        'success' => 'emitida',
                        'danger' => 'anulada',
                        'warning' => 'rechazada',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'emitida' => 'Emitida',
                        'anulada' => 'Anulada',
                        'rechazada' => 'Rechazada',
                        default => $state,
                    })
                    ->sortable(),



               /* TextColumn::make('cod_operacion')
                    ->label('Cód. Operación')
                    ->searchable()
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),*/
            ])
            ->filters([
                SelectFilter::make('estado_venta')
                    ->label('Estado')
                    ->options([
                        'emitida' => 'Emitida',
                        'anulada' => 'Anulada',
                        'rechazada' => 'Rechazada',
                    ]),

                SelectFilter::make('metodo_pago')
                    ->label('Método de Pago')
                    ->options([
                        'efectivo' => 'Efectivo',
                        'tarjeta' => 'Tarjeta',
                        'yape' => 'Yape',
                        'plin' => 'Plin',
                        'transferencia' => 'Transferencia',
                    ]),

                SelectFilter::make('tipo_comprobante')
                    ->label('Tipo de Comprobante')
                    ->options([
                        'boleta' => 'Boleta',
                        'factura' => 'Factura',
                        'ticket' => 'Ticket',
                        'nota_credito' => 'Nota de Crédito',
                        'nota_debito' => 'Nota de débito',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value']) || $data['value'] === null) {
                            return $query;
                        }

                        if ($data['value'] === 'sin_comprobante') {
                            return $query->whereDoesntHave('comprobantes');
                        }

                        return $query->whereHas('comprobantes', function (Builder $query) use ($data) {
                            $query->where('tipo', $data['value']);
                        });
                    }),

                Filter::make('fecha_venta')
                    ->label('Rango de Fechas')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('desde')
                            ->label('Desde'),
                        \Filament\Forms\Components\DatePicker::make('hasta')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha_venta', '>=', $date),
                            )
                            ->when(
                                $data['hasta'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha_venta', '<=', $date),
                            );
                    }),
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
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
