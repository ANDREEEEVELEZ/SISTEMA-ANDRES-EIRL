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

        Notification::make()
            ->title('Ticket anulado')
            ->body("El ticket de la venta #{$record->id} ha sido anulado exitosamente.")
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
                        // Obtener solo el comprobante principal (NO las notas)
                        $comprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();

                        if (!$comprobante) {
                            return 'Sin comprobante';
                        }

                        $texto = strtoupper($comprobante->tipo) . " {$comprobante->serie}-{$comprobante->correlativo}";

                        // Si el comprobante está anulado, mostrar con qué nota se anuló
                        if ($comprobante->estado === 'anulado') {
                            $nota = $record->comprobantes()
                                ->whereIn('tipo', ['nota de credito', 'nota de debito'])
                                ->first();

                            if ($nota) {
                                $tipoNotaAbrev = $nota->tipo === 'nota de credito' ? 'NC' : 'ND';
                                $texto .= "\n→ {$tipoNotaAbrev} {$nota->serie}-{$nota->correlativo}";
                            }
                        }

                        return $texto;
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
                        $comprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();

                        if (!$comprobante || $comprobante->estado !== 'anulado') {
                            return null;
                        }

                        $nota = $record->comprobantes()
                            ->whereIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();

                        if ($nota) {
                            $tipoNota = $nota->tipo === 'nota de credito' ? 'Nota de Crédito' : 'Nota de Débito';
                            return "Anulado con {$tipoNota}: {$nota->serie}-{$nota->correlativo}";
                        }

                        return 'Anulado';
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
                Action::make('Ver')
                    ->label('ver')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn ($record) => route('filament.admin.resources.ventas.edit', $record))
                    ->openUrlInNewTab(false),

                Action::make('imprimir')
                    ->label('Imprimir')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn ($record) => route('comprobante.imprimir', $record->id))
                    ->openUrlInNewTab(true),

                Action::make('anular')
                    ->label('Anular')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function ($record) {
                        // Verificar que el comprobante esté emitido
                        $comprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();

                        return $comprobante && $comprobante->estado === 'emitido';
                    })
                    ->action(function ($record) {
                        $comprobante = $record->comprobantes()->first();
                        $tipoComprobante = $comprobante ? $comprobante->tipo : 'ticket';

                        // Para boletas y facturas: mostrar mensaje informativo
                        if (in_array($tipoComprobante, ['boleta', 'factura'])) {
                            // Mostrar notificación informativa
                            Notification::make()
                                ->title('Anulación de ' . ucfirst($tipoComprobante))
                                ->body('Para anular una ' . $tipoComprobante . ', debe crear una Nota de Crédito desde el módulo correspondiente.')
                                ->warning()
                                ->send();
                        } else {
                            // Para tickets: anulación directa
                            static::anularTicket($record, 'Ticket anulado desde la tabla');
                        }
                    })
                    ->requiresConfirmation(function ($record) {
                        $comprobante = $record->comprobantes()->first();
                        $tipoComprobante = $comprobante ? $comprobante->tipo : 'ticket';

                        // Solo confirmar para tickets (anulación directa)
                        return !in_array($tipoComprobante, ['boleta', 'factura']);
                    })
                    ->modalHeading(function ($record) {
                        $comprobante = $record->comprobantes()->first();
                        $tipoComprobante = $comprobante ? $comprobante->tipo : 'ticket';

                        if (!in_array($tipoComprobante, ['boleta', 'factura'])) {
                            return 'Anular Ticket';
                        }
                        return null;
                    })
                    ->modalDescription(function ($record) {
                        $comprobante = $record->comprobantes()->first();
                        $tipoComprobante = $comprobante ? $comprobante->tipo : 'ticket';

                        if (!in_array($tipoComprobante, ['boleta', 'factura'])) {
                            return '¿Está seguro de que desea anular este ticket? Esta acción no se puede deshacer.';
                        }
                        return null;
                    })
                    ->form(function ($record) {
                        $comprobante = $record->comprobantes()->first();
                        $tipoComprobante = $comprobante ? $comprobante->tipo : 'ticket';

                        // Solo mostrar formulario para tickets
                        if (!in_array($tipoComprobante, ['boleta', 'factura'])) {
                            return [
                                \Filament\Forms\Components\Textarea::make('observacion')
                                    ->label('Motivo de anulación')
                                    ->required()
                                    ->maxLength(500)
                                    ->placeholder('Ingrese el motivo de la anulación del ticket'),
                            ];
                        }
                        return [];
                    }),
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
