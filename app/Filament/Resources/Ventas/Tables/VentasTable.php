<?php

namespace App\Filament\Resources\Ventas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class VentasTable
{
    protected static function anularTicket($record, $observacion = null): void
    {
        $record->update([
            'estado_venta' => 'anulada',
            'observacion' => $observacion ?? 'Ticket anulado'
        ]);

        // Marcar el comprobante principal como anulado (tickets no tienen notas relacionadas)
        $comprobante = $record->comprobantes()
            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
            ->first();

        if ($comprobante) {
            // Guardar estado y motivo de anulación (si se pasó observación)
            $comprobante->update([
                'estado' => 'anulado',
                'motivo_anulacion' => $observacion ?? null,
            ]);
        }

        // REVERTIR INVENTARIO: Crear movimientos de entrada para devolver el stock
        foreach ($record->detalleVentas as $detalle) {
            $producto = $detalle->producto;

            if ($producto) {
                // Incrementar el stock del producto
                $stockAnterior = $producto->stock_total;
                $nuevoStock = $stockAnterior + $detalle->cantidad_venta;

                $producto->update([
                    'stock_total' => $nuevoStock
                ]);

                // Registrar movimiento de inventario (ENTRADA - reversión)
                \App\Models\MovimientoInventario::create([
                    'producto_id' => $producto->id,
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'tipo' => 'entrada',
                    'cantidad_movimiento' => $detalle->cantidad_venta,
                    'motivo_movimiento' => "Reversión por anulación de Venta #{$record->id}",
                    'fecha_movimiento' => now(),
                ]);
            }
        }

        Notification::make()
            ->title('Ticket anulado')
            ->body("El ticket de la venta #{$record->id} ha sido anulado exitosamente y el inventario ha sido restablecido.")
            ->success()
            ->send();
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('comprobantes')
                    ->label('Comprobante')
                    ->formatStateUsing(function ($record) {
                        // Mostrar solo el comprobante principal (SIN referencia a la nota)
                        $comprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();

                        if (!$comprobante) {
                            return 'Sin comprobante';
                        }

                        return strtoupper($comprobante->tipo) . " {$comprobante->serie}-{$comprobante->correlativo}";
                    })
                    ->badge()
                    ->color(function ($record) {
                        $comprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();

                        if (!$comprobante) {
                            return 'danger';
                        }

                        // Color según el estado del comprobante
                        if ($comprobante->estado === 'emitido') {
                            return 'success';
                        } elseif ($comprobante->estado === 'anulado') {
                            return 'warning';
                        } elseif ($comprobante->estado === 'rechazado') {
                            return 'danger';
                        }

                        return 'gray';
                    })
                    ->description(function ($record) {
                        // Mostrar solo la referencia a la nota (segunda línea)
                        $comprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();

                        if (!$comprobante || $comprobante->estado !== 'anulado') {
                            return null;
                        }

                        $nota = $record->comprobantes()
                            ->whereIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();

                        if (!$nota) {
                            return null;
                        }

                        $tipoNotaAbrev = $nota->tipo === 'nota de credito' ? 'NC' : 'ND';
                        return "→ {$tipoNotaAbrev} {$nota->serie}-{$nota->correlativo}";
                    }),


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
                    ->getStateUsing(function ($record) {
                        // Priorizar nombre_cliente_temporal para tickets
                        if (!empty($record->nombre_cliente_temporal)) {
                            return $record->nombre_cliente_temporal;
                        }
                        return $record->cliente ? $record->cliente->nombre_razon : '-';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function ($query) use ($search) {
                            $query->where('nombre_cliente_temporal', 'like', "%{$search}%")
                                  ->orWhereHas('cliente', function ($query) use ($search) {
                                      $query->where('nombre_razon', 'like', "%{$search}%");
                                  });
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->leftJoin('clientes', 'ventas.cliente_id', '=', 'clientes.id')
                            ->orderByRaw("COALESCE(ventas.nombre_cliente_temporal, clientes.nombre_razon) {$direction}")
                            ->select('ventas.*');
                    })
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
                    ->getStateUsing(function ($record) {
                        return $record->cliente ? $record->cliente->num_doc : '-';
                    })
                    ->searchable(),
                    //->toggleable(),


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

                BadgeColumn::make('estado_comprobante')
                    ->label('Estado')
                    ->getStateUsing(function ($record) {
                        // Obtener el estado del comprobante principal (no notas)
                        $comprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();

                        return $comprobante ? $comprobante->estado : 'sin comprobante';
                    })
                    ->colors([
                        'success' => 'emitido',
                        'danger' => 'anulado',
                        'warning' => 'rechazado',
                        'gray' => 'sin comprobante',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'emitido' => 'Emitido',
                        'anulado' => 'Anulado',
                        'rechazado' => 'Rechazado',
                        'sin comprobante' => 'Sin Comprobante',
                        default => ucfirst($state),
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->leftJoin('comprobantes', function ($join) {
                                $join->on('ventas.id', '=', 'comprobantes.venta_id')
                                     ->whereNotIn('comprobantes.tipo', ['nota de credito', 'nota de debito']);
                            })
                            ->orderBy('comprobantes.estado', $direction)
                            ->select('ventas.*');
                    }),

            ])
            ->filters([
                SelectFilter::make('estado_comprobante')
                    ->label('Estado Comprobante')
                    ->options([
                        'emitido' => 'Emitido',
                        'anulado' => 'Anulado',
                        'rechazado' => 'Rechazado',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value']) || $data['value'] === null) {
                            return $query;
                        }

                        return $query->whereHas('comprobantes', function (Builder $q) use ($data) {
                            $q->where('estado', $data['value'])
                              ->whereNotIn('tipo', ['nota de credito', 'nota de debito']);
                        });
                    }),

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
                Action::make('imprimir')
                    ->label('Imprimir')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn ($record) => route('comprobante.imprimir', $record->id))
                    ->extraAttributes(['style' => 'min-width:110px; display:inline-flex; align-items:center; justify-content:flex-start; gap:2px; padding-left:2px; margin-right:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; padding:4px 4px; line-height:1;'])
                    ->openUrlInNewTab(true),

                // Placeholder to keep spacing when 'Anular' is not visible
                Action::make('anular_placeholder')
                    ->label('Anular')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->disabled()
                    ->visible(function ($record) {
                        $comprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();

                        // Mostrar placeholder cuando ANULAR no esté disponible
                        return !($comprobante && $comprobante->estado === 'emitido');
                    })
                    ->extraAttributes(['style' => 'min-width:110px; display:inline-flex; align-items:center; justify-content:flex-start; gap:2px; padding-left:2px; white-space:nowrap; opacity:0; pointer-events:none; padding:4px 4px; line-height:1;']),

                Action::make('anular')
                    ->label(function ($record) {
                        $comprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();
                        $tipoComprobante = $comprobante ? $comprobante->tipo : 'ticket';

                        return in_array($tipoComprobante, ['boleta', 'factura']) ? 'Emitir nota' : 'Anular';
                    })
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function ($record) {
                        // Verificar que el comprobante principal esté emitido
                        $comprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();

                        return $comprobante && $comprobante->estado === 'emitido';
                    })
                    ->extraAttributes(['style' => 'min-width:110px; display:inline-flex; align-items:center; justify-content:flex-start; gap:2px; padding-left:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; padding:4px 4px; line-height:1;'])
                    ->form([
                        \Filament\Forms\Components\Textarea::make('motivo_anulacion')
                            ->label('Motivo de Anulación')
                            ->required()
                            ->rows(3)
                            ->placeholder('Motivo (requerido para tickets)'),
                    ])
                    ->action(function ($record, array $data) {
                            $comprobante = $record->comprobantes()
                                ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                                ->first();
                            $tipoComprobante = $comprobante ? $comprobante->tipo : 'ticket';

                            // Para boletas y facturas: mostrar mensaje informativo
                            if (in_array($tipoComprobante, ['boleta', 'factura'])) {
                                Notification::make()
                                    ->title('Anulación de ' . ucfirst($tipoComprobante))
                                    ->body('Para anular una ' . $tipoComprobante . ', debe crear una Nota de Crédito desde el módulo correspondiente.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Para tickets: validar motivo y ejecutar anulación
                            $motivo = trim($data['motivo_anulacion'] ?? '');
                            if (empty($motivo)) {
                                Notification::make()
                                    ->title('Motivo requerido')
                                    ->body('Debe ingresar un motivo de anulación para tickets.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            static::anularTicket($record, $motivo);
                        })
                    ->requiresConfirmation(function ($record) {
                        $comprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();
                        $tipoComprobante = $comprobante ? $comprobante->tipo : 'ticket';

                        // Solo confirmar para tickets (anulación directa)
                        return !in_array($tipoComprobante, ['boleta', 'factura']);
                    })
                    ->modalHeading(function ($record) {
                        $comprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();
                        $tipoComprobante = $comprobante ? $comprobante->tipo : 'ticket';

                        if (!in_array($tipoComprobante, ['boleta', 'factura'])) {
                            return 'Anular Ticket';
                        }
                        return null;
                    })
                    ->modalDescription(function ($record) {
                        $comprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();
                        $tipoComprobante = $comprobante ? $comprobante->tipo : 'ticket';

                        if (!in_array($tipoComprobante, ['boleta', 'factura'])) {
                            return '¿Está seguro de que desea anular este ticket? Esta acción no se puede deshacer.';
                        }
                        return null;
                    })
                    ,
            ])

            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
