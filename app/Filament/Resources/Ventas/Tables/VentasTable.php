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
use App\Services\SunatService;
use Illuminate\Support\Facades\Log;

class VentasTable
{
    public static function anularTicket($record, $observacion = null): void
    {
        $record->update([
            'estado_venta' => 'anulada'
        ]);

        // Marcar el comprobante principal como anulado (tickets no tienen notas relacionadas)
        $comprobante = $record->comprobantes()
            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
            ->first();

        if ($comprobante) {
            // Guardar estado y motivo de anulación
            $comprobante->update([
                'estado' => 'anulado',
                'motivo_anulacion' => $observacion ?? 'Ticket anulado',
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

    protected static function crearNotaCredito($record, array $data): void
    {
        try {
            $comprobante = $record->comprobantes()
                ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                ->first();

            if (!$comprobante) {
                Notification::make()
                    ->title('Error')
                    ->body('No se encontró el comprobante a anular.')
                    ->danger()
                    ->send();
                return;
            }

            $tipoComprobante = $comprobante->tipo;
            $tipoNotaDB = 'nota de credito';

            // Buscar la serie configurada para la nota
            $serieNota = \App\Models\SerieComprobante::where('tipo', $tipoNotaDB)
                ->where('aplica_a', $tipoComprobante)
                ->where('serie', $data['serie_nota'])
                ->first();

            if (!$serieNota) {
                Notification::make()
                    ->title('Error')
                    ->body('No se encontró la serie de comprobante configurada para esta nota.')
                    ->danger()
                    ->send();
                return;
            }

            // Crear la nota de crédito
            $nota = \App\Models\Comprobante::create([
                'venta_id' => $record->id,
                'serie_comprobante_id' => $serieNota->id,
                'tipo' => $tipoNotaDB,
                'codigo_tipo_nota' => $data['codigo_tipo_nota'] ?? '01',
                'serie' => $data['serie_nota'],
                'correlativo' => $data['numero_nota'],
                'fecha_emision' => now(),
                'sub_total' => $record->subtotal_venta,
                'igv' => $record->igv,
                'total' => $record->total_venta,
                'estado' => 'emitido',
                'motivo_anulacion' => $data['motivo_nota'],
            ]);

            // Actualizar el último número de la serie
            $serieNota->increment('ultimo_numero');

            // Actualizar la venta como anulada
            $record->update(['estado_venta' => 'anulada']);

            // Actualizar el comprobante original como anulado
            $comprobante->update([
                'estado' => 'anulado',
                'motivo_anulacion' => "Anulado con NC {$data['serie_nota']}-{$data['numero_nota']}",
            ]);

            // Crear la relación entre el comprobante original y la nota
            \App\Models\ComprobanteRelacion::create([
                'comprobante_origen_id' => $comprobante->id,
                'comprobante_relacionado_id' => $nota->id,
                'tipo_relacion' => $tipoNotaDB,
            ]);

            // REVERTIR INVENTARIO
            foreach ($record->detalleVentas as $detalle) {
                $producto = $detalle->producto;

                if ($producto) {
                    $stockAnterior = $producto->stock_total;
                    $nuevoStock = $stockAnterior + $detalle->cantidad_venta;

                    $producto->update([
                        'stock_total' => $nuevoStock
                    ]);

                    \App\Models\MovimientoInventario::create([
                        'producto_id' => $producto->id,
                        'user_id' => \Illuminate\Support\Facades\Auth::id(),
                        'tipo' => 'entrada',
                        'cantidad_movimiento' => $detalle->cantidad_venta,
                        'motivo_movimiento' => "Reversión por anulación de Venta #{$record->id} con NC",
                        'fecha_movimiento' => now(),
                    ]);
                }
            }

            // ENVIAR NOTA DE CRÉDITO A SUNAT (solo si el comprobante original ya fue enviado)
            try {
                // Verificar si el comprobante original tiene XML/CDR guardados (fue enviado)
                $yaEnviadoASunat = !empty($comprobante->ruta_xml) || !empty($comprobante->ruta_cdr) || !empty($comprobante->codigo_sunat);

                // Validación adicional para boletas (requieren ticket_sunat)
                if ($comprobante->tipo === 'boleta') {
                    $yaEnviadoASunat = $yaEnviadoASunat && !empty($comprobante->ticket_sunat);
                }

                if ($yaEnviadoASunat) {
                    // El comprobante YA está en SUNAT, enviar la nota
                    $sunatService = new SunatService();
                    $resultadoEnvio = $sunatService->enviarNotaCredito($nota);

                    if ($resultadoEnvio['success']) {
                        Notification::make()
                            ->title(' Nota de Crédito Aceptada por SUNAT')
                            ->body("Se creó y envió exitosamente la Nota de Crédito {$data['serie_nota']}-{$data['numero_nota']} a SUNAT. El inventario ha sido restablecido.")
                            ->success()
                            ->duration(10000) // 10 segundos
                            ->send();
                    } else {
                        Notification::make()
                            ->title(' Nota de Crédito Creada (Error en envío)')
                            ->body("Se creó la Nota de Crédito {$data['serie_nota']}-{$data['numero_nota']} pero falló el envío a SUNAT: {$resultadoEnvio['message']}")
                            ->warning()
                            ->duration(12000) // 12 segundos
                            ->send();
                    }
                } else {
                    // El comprobante NO ha sido enviado a SUNAT todavía
                    $tipoDoc = strtoupper($comprobante->tipo);
                    $mensaje = " Nota de Crédito {$data['serie_nota']}-{$data['numero_nota']} creada localmente.\n\n";

                    if ($comprobante->tipo === 'boleta') {
                        $mensaje .= " La boleta {$comprobante->serie}-{$comprobante->correlativo} NO ha sido enviada a SUNAT aún .\n\n";
                        $mensaje .= " La boleta ANULADA será excluida automáticamente del próximo Resumen Diario.\n\n";
                       // $mensaje .= " No necesitas hacer nada más. Para SUNAT, esta boleta no existirá.";
                    } else {
                        $mensaje .= "El {$tipoDoc} {$comprobante->serie}-{$comprobante->correlativo} no fue enviado a SUNAT.\n\n";
                        $mensaje .= " La Nota de Crédito está lista. Use 'Reenviar' si necesita enviarla después.";
                    }

                    Notification::make()
                        ->title(' Nota de Crédito Creada')
                        ->body($mensaje)
                        ->success()
                        ->duration(12000) // 12 segundos
                        ->send();
                }
            } catch (\Exception $envioException) {
                Log::error('Error al enviar nota de crédito a SUNAT', [
                    'nota_id' => $nota->id,
                    'error' => $envioException->getMessage(),
                ]);

                Notification::make()
                    ->title(' Nota de Crédito Creada (Error en envío)')
                    ->body("Se creó la Nota de Crédito {$data['serie_nota']}-{$data['numero_nota']} pero falló el envío a SUNAT. Use el botón 'Reenviar' para intentar nuevamente.")
                    ->warning()
                    ->duration(12000) // 12 segundos
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Error al crear la nota: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('comprobantes')
                    ->label('Comprobante')
                    ->getStateUsing(function ($record) {
                        // Obtener solo el comprobante principal (sin notas) y devolver una cadena
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
                        // Mostrar referencia a la nota solo cuando el comprobante principal esté anulado
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
                    ->label('Pago')
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
                       // 'nota_debito' => 'Nota de débito',
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
                // BOTÓN 1: Reenviar a SUNAT (solo visible si hay ERROR en el último comprobante)
                Action::make('reenviar_sunat')
                    ->label('Reenviar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(function ($record) {
                        // Obtener el ÚLTIMO comprobante emitido (puede ser factura, boleta o nota)
                        $ultimoComprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['ticket']) // Excluir solo tickets
                            ->where('estado', 'emitido')
                            ->latest('id')
                            ->first();

                        // Mostrar SOLO si hay error de envío
                        return $ultimoComprobante &&
                               !empty($ultimoComprobante->error_envio);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Reenviar a SUNAT')
                    ->modalDescription(function ($record) {
                        $ultimoComprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['ticket'])
                            ->where('estado', 'emitido')
                            ->latest('id')
                            ->first();

                        if (!$ultimoComprobante) {
                            return '¿Desea enviar este comprobante a SUNAT?';
                        }

                        $tipo = strtoupper($ultimoComprobante->tipo);
                        $serie = $ultimoComprobante->serie;
                        $correlativo = $ultimoComprobante->correlativo;

                        return " {$tipo} {$serie}-{$correlativo}\n\n¿Desea reintentar el envío a SUNAT?";
                    })
                    ->action(function ($record) {
                        $ultimoComprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['ticket'])
                            ->where('estado', 'emitido')
                            ->latest('id')
                            ->first();

                        if (!$ultimoComprobante) {
                            Notification::make()
                                ->title('Error')
                                ->body('No se encontró el comprobante')
                                ->danger()
                                ->send();
                            return;
                        }

                        try {
                            $sunatService = new SunatService();

                            // Determinar qué método usar según el tipo
                            if ($ultimoComprobante->tipo === 'factura' || $ultimoComprobante->tipo === 'boleta') {
                                $resultado = $sunatService->reenviarComprobante($ultimoComprobante);
                            } elseif ($ultimoComprobante->tipo === 'nota de credito') {
                                $resultado = $sunatService->enviarNotaCredito($ultimoComprobante);
                            } else {
                                throw new \Exception('Tipo de comprobante no soportado para envío a SUNAT');
                            }

                            if ($resultado['success']) {
                                Notification::make()
                                    ->title('Enviado a SUNAT')
                                    ->body($resultado['message'] ?? 'Comprobante aceptado por SUNAT')
                                    ->success()
                                    ->send();

                                Log::info("Comprobante #{$ultimoComprobante->id} reenviado exitosamente", [
                                    'tipo' => $ultimoComprobante->tipo,
                                    'codigo' => $resultado['codigo'] ?? null,
                                ]);
                            } else {
                                Notification::make()
                                    ->title(' Error de SUNAT')
                                    ->body($resultado['message'] ?? 'No se pudo enviar a SUNAT')
                                    ->danger()
                                    ->send();

                                Log::warning(" Error al reenviar comprobante #{$ultimoComprobante->id}", [
                                    'error' => $resultado['message'] ?? 'Error desconocido',
                                ]);
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(' Error')
                                ->body('Error al comunicarse con SUNAT: ' . $e->getMessage())
                                ->danger()
                                ->send();

                            Log::error(" Excepción al reenviar comprobante #{$ultimoComprobante->id}", [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    })
                    ->extraAttributes(['style' => 'min-width:110px; display:inline-flex; align-items:center; justify-content:flex-start; gap:2px; padding-left:2px; margin-right:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; padding:4px 4px; line-height:1;']),

                // BOTÓN 2: Imprimir (siempre visible)
                Action::make('imprimir')
                    ->label('Imprimir')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn ($record) => route('comprobante.imprimir', $record->id))
                    ->extraAttributes(['style' => 'min-width:110px; display:inline-flex; align-items:center; justify-content:flex-start; gap:2px; padding-left:2px; margin-right:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; padding:4px 4px; line-height:1;'])
                    ->openUrlInNewTab(true),

                // BOTÓN 3: Imprimir Nota O Anular/Emitir Nota (uno u otro, siempre 2 botones totales)
                // Opción A: Imprimir Nota (si existe nota emitida)
                Action::make('imprimir_nota')
                    ->label('Nota')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->visible(function ($record) {
                        $nota = $record->comprobantes()
                            ->whereIn('tipo', ['nota de credito', 'nota de debito'])
                            ->where('estado', 'emitido')
                            ->first();
                        return $nota !== null;
                    })
                    ->url(function ($record) {
                        $nota = $record->comprobantes()
                            ->whereIn('tipo', ['nota de credito', 'nota de debito'])
                            ->where('estado', 'emitido')
                            ->first();
                        return route('nota.imprimir', $nota->id);
                    })
                    ->extraAttributes(['style' => 'min-width:110px; display:inline-flex; align-items:center; justify-content:flex-start; gap:2px; padding-left:2px; margin-right:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; padding:4px 4px; line-height:1;'])
                    ->openUrlInNewTab(true),

                // Opción B: Anular/Emitir Nota (si NO hay nota emitida Y está emitido)
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
                        // Solo visible si NO hay nota emitida Y el comprobante está emitido
                        $comprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();

                        $nota = $record->comprobantes()
                            ->whereIn('tipo', ['nota de credito', 'nota de debito'])
                            ->where('estado', 'emitido')
                            ->first();

                        return $nota === null && $comprobante && $comprobante->estado === 'emitido';
                    })
                    ->extraAttributes(['style' => 'min-width:110px; display:inline-flex; align-items:center; justify-content:flex-start; gap:2px; padding-left:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; padding:4px 4px; line-height:1;'])
                    ->fillForm(function ($record) {
                        // Pre-cargar datos del comprobante actual
                        $comprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();

                        if (!$comprobante) {
                            return [];
                        }

                        $tipoComprobante = $comprobante->tipo;

                        // Cargar series automáticas de la nota según el tipo de comprobante
                        $tipoNotaDB = 'nota de credito';
                        $serieNota = \App\Models\SerieComprobante::where('tipo', $tipoNotaDB)
                            ->where('aplica_a', $tipoComprobante)
                            ->latest('id')
                            ->first();

                        $serieNotaDefault = $tipoComprobante === 'boleta' ? 'BC03' : 'FC03';
                        $numeroNotaDefault = $serieNota ? ($serieNota->ultimo_numero + 1) : 1;

                        return [
                            'tipo_comprobante' => $tipoComprobante,
                            'serie_comprobante' => $comprobante->serie,
                            'numero_comprobante' => $comprobante->correlativo,
                            'serie_nota' => $serieNota ? $serieNota->serie : $serieNotaDefault,
                            'numero_nota' => $numeroNotaDefault,
                            'codigo_tipo_nota' => '01', // Anulación de la operación
                        ];
                    })
                    ->form([
                        // BOLETAS/FACTURAS: Formulario de emisión de nota
                        \Filament\Forms\Components\Select::make('codigo_tipo_nota')
                            ->label('Motivo de la Nota de Crédito')
                            ->options([
                                '01' => '01 - Anulación de la Operación',
                            ])
                            ->default('01')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->visible(fn ($record) => in_array(
                                $record->comprobantes()->whereNotIn('tipo', ['nota de credito', 'nota de debito'])->first()?->tipo ?? '',
                                ['boleta', 'factura']
                            )),

                        \Filament\Forms\Components\TextInput::make('serie_nota')
                            ->label('Serie de la Nota de Crédito')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->visible(fn ($record) => in_array(
                                $record->comprobantes()->whereNotIn('tipo', ['nota de credito', 'nota de debito'])->first()?->tipo ?? '',
                                ['boleta', 'factura']
                            )),

                        \Filament\Forms\Components\TextInput::make('numero_nota')
                            ->label('Número de la Nota')
                            ->required()
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->visible(fn ($record) => in_array(
                                $record->comprobantes()->whereNotIn('tipo', ['nota de credito', 'nota de debito'])->first()?->tipo ?? '',
                                ['boleta', 'factura']
                            )),

                        \Filament\Forms\Components\Textarea::make('motivo_nota')
                            ->label('Motivo de la Nota')
                            ->required()
                            ->rows(3)
                            ->placeholder('Describe el motivo de la nota de crédito...')
                            ->visible(fn ($record) => in_array(
                                $record->comprobantes()->whereNotIn('tipo', ['nota de credito', 'nota de debito'])->first()?->tipo ?? '',
                                ['boleta', 'factura']
                            )),

                        // TICKETS: Formulario simple de anulación
                        \Filament\Forms\Components\Textarea::make('motivo_anulacion')
                            ->label('Motivo de Anulación')
                            ->required()
                            ->rows(3)
                            ->placeholder('Motivo de anulación del ticket...')
                            ->visible(fn ($record) =>
                                $record->comprobantes()->whereNotIn('tipo', ['nota de credito', 'nota de debito'])->first()?->tipo === 'ticket'
                            ),
                    ])
                    ->action(function ($record, array $data) {
                        $comprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();
                        $tipoComprobante = $comprobante ? $comprobante->tipo : 'ticket';

                        // Si es ticket, anular directamente
                        if ($tipoComprobante === 'ticket') {
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
                            return;
                        }

                        // Si es boleta/factura, crear nota de crédito
                        if (in_array($tipoComprobante, ['boleta', 'factura'])) {
                            static::crearNotaCredito($record, $data);
                            return;
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading(function ($record) {
                        $comprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();
                        $tipoComprobante = $comprobante ? $comprobante->tipo : 'ticket';

                        if ($tipoComprobante === 'ticket') {
                            return 'Anular Ticket';
                        }

                        return 'Emitir Nota de Crédito';
                    })
                    ->modalDescription(function ($record) {
                        $comprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();
                        $tipoComprobante = $comprobante ? $comprobante->tipo : 'ticket';

                        if ($tipoComprobante === 'ticket') {
                            return '¿Está seguro de que desea anular este ticket? Esta acción no se puede deshacer.';
                        }

                        return '¿Está seguro de que desea emitir esta nota de crédito? Se anulará el comprobante original y se restablecerá el inventario.';
                    })
                    ->modalSubmitActionLabel(fn ($record) =>
                        $record->comprobantes()->whereNotIn('tipo', ['nota de credito', 'nota de debito'])->first()?->tipo === 'ticket'
                            ? 'Anular'
                            : 'Emitir Nota'
                    ),

                // Placeholder invisible cuando NO hay ninguno de los dos anteriores
                Action::make('segundo_boton_placeholder')
                    ->label('Acción')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->disabled()
                    ->visible(function ($record) {
                        $comprobante = $record->comprobantes()
                            ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                            ->first();

                        $nota = $record->comprobantes()
                            ->whereIn('tipo', ['nota de credito', 'nota de debito'])
                            ->where('estado', 'emitido')
                            ->first();

                        // Mostrar placeholder si NO hay nota Y el comprobante NO está emitido
                        return $nota === null && !($comprobante && $comprobante->estado === 'emitido');
                    })
                    ->extraAttributes(['style' => 'min-width:110px; display:inline-flex; align-items:center; justify-content:flex-start; gap:2px; padding-left:2px; white-space:nowrap; opacity:0; pointer-events:none; padding:4px 4px; line-height:1;']),
            ])

            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
