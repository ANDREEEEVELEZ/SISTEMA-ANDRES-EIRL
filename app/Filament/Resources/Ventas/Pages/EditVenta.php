<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use App\Services\SunatService;
use Illuminate\Support\Facades\Log;

class EditVenta extends EditRecord
{
    protected static string $resource = VentaResource::class;

    // Método para montar la página y deshabilitar el formulario
    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Deshabilitar todos los campos del formulario
        $this->form->disabled();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record) {
            // Cargar datos del comprobante
            $comprobante = $this->record->comprobantes()->first();
            if ($comprobante) {
                $data['tipo_comprobante'] = $comprobante->tipo;
                $data['serie'] = $comprobante->serie;
                $data['numero'] = str_pad($comprobante->correlativo, 6, '0', STR_PAD_LEFT);
                $data['fecha_emision'] = $comprobante->fecha_emision;
                // Incluir motivo de anulación si existe para mostrarlo en el formulario de edición
                $data['motivo_anulacion'] = $comprobante->motivo_anulacion;
            }

            // Cargar nombre temporal del cliente si existe
            if (!empty($this->record->nombre_cliente_temporal)) {
                $data['cliente_ticket_nombre'] = $this->record->nombre_cliente_temporal;
            }
        }
        return $data;
    }

    protected function getHeaderActions(): array
    {
        $actions = [
           /* \Filament\Actions\Action::make('nueva_venta')
                ->label('Nueva Venta')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->url(fn () => VentaResource::getUrl('create'))
                ->outlined(),*/
            \Filament\Actions\Action::make('imprimir')
                ->label('Imprimir')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(fn () => route('comprobante.imprimir', $this->record->id))
                ->extraAttributes(['style' => 'min-width:110px; display:inline-flex; align-items:center; justify-content:flex-start; gap:2px; padding-left:2px; margin-right:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; padding:4px 4px; line-height:1;'])
                ->openUrlInNewTab(true),
        ];


            $actions[] = \Filament\Actions\Action::make('imprimir_nota')
                ->label('Nota')
                ->icon('heroicon-o-document-text')
                ->color('warning')
                ->visible(function () {
                    $nota = $this->record->comprobantes()
                        ->whereIn('tipo', ['nota de credito', 'nota de debito'])
                        ->where('estado', 'emitido')
                        ->first();
                    return $nota !== null;
                })
                ->url(function () {
                    $nota = $this->record->comprobantes()
                        ->whereIn('tipo', ['nota de credito', 'nota de debito'])
                        ->where('estado', 'emitido')
                        ->first();
                    return $nota ? route('nota.imprimir', $nota->id) : null;
                })
                ->openUrlInNewTab(true)
                ->extraAttributes(['style' => 'min-width:110px; display:inline-flex; align-items:center; justify-content:flex-start; gap:2px; padding-left:2px; margin-right:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; padding:4px 4px; line-height:1;']);

        $actions[] = \Filament\Actions\Action::make('anular')
            ->label(function () {
                $comprobante = $this->record->comprobantes()
                    ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                    ->first();
                $tipoComprobante = $comprobante ? $comprobante->tipo : 'ticket';

                return in_array($tipoComprobante, ['boleta', 'factura']) ? 'Emitir nota' : 'Anular';
            })
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(function () {
                $comprobante = $this->record->comprobantes()
                    ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                    ->first();

                $nota = $this->record->comprobantes()
                    ->whereIn('tipo', ['nota de credito', 'nota de debito'])
                    ->where('estado', 'emitido')
                    ->first();

                // Visible solo si NO hay nota emitida Y el comprobante está emitido
                return $nota === null && $comprobante && $comprobante->estado === 'emitido' && !in_array($comprobante->tipo, ['ticket']);
            })
            ->extraAttributes(['style' => 'min-width:110px; display:inline-flex; align-items:center; justify-content:flex-start; gap:2px; padding-left:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; padding:4px 4px; line-height:1;'])
            ->fillForm(function () {
                $comprobante = $this->record->comprobantes()
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

                $serieNotaDefault = $tipoComprobante === 'boleta' ? 'BC01' : 'FC01';
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
                    ->visible(fn () => in_array(
                        $this->record->comprobantes()->whereNotIn('tipo', ['nota de credito', 'nota de debito'])->first()?->tipo ?? '',
                        ['boleta', 'factura']
                    )),

                \Filament\Forms\Components\TextInput::make('serie_nota')
                    ->label('Serie de la Nota de Crédito')
                    ->required()
                    ->disabled()
                    ->dehydrated()
                    ->visible(fn () => in_array(
                        $this->record->comprobantes()->whereNotIn('tipo', ['nota de credito', 'nota de debito'])->first()?->tipo ?? '',
                        ['boleta', 'factura']
                    )),

                \Filament\Forms\Components\TextInput::make('numero_nota')
                    ->label('Número de la Nota')
                    ->required()
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->visible(fn () => in_array(
                        $this->record->comprobantes()->whereNotIn('tipo', ['nota de credito', 'nota de debito'])->first()?->tipo ?? '',
                        ['boleta', 'factura']
                    )),

                \Filament\Forms\Components\Textarea::make('motivo_nota')
                    ->label('Motivo de la Nota')
                    ->required()
                    ->rows(3)
                    ->placeholder('Describe el motivo de la nota de crédito...')
                    ->visible(fn () => in_array(
                        $this->record->comprobantes()->whereNotIn('tipo', ['nota de credito', 'nota de debito'])->first()?->tipo ?? '',
                        ['boleta', 'factura']
                    )),
            ])
            ->action(function (array $data) {
                // Reproducir la lógica de crearNotaCredito del Table
                try {
                    $comprobante = $this->record->comprobantes()
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
                        'venta_id' => $this->record->id,
                        'serie_comprobante_id' => $serieNota->id,
                        'tipo' => $tipoNotaDB,
                        'codigo_tipo_nota' => $data['codigo_tipo_nota'] ?? '01',
                        'serie' => $data['serie_nota'],
                        'correlativo' => $data['numero_nota'],
                        'fecha_emision' => now(),
                        'sub_total' => $this->record->subtotal_venta,
                        'igv' => $this->record->igv,
                        'total' => $this->record->total_venta,
                        'estado' => 'emitido',
                        'motivo_anulacion' => $data['motivo_nota'],
                    ]);

                    // Actualizar el último número de la serie
                    $serieNota->increment('ultimo_numero');

                    // Actualizar la venta como anulada
                    $this->record->update(['estado_venta' => 'anulada']);

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
                    foreach ($this->record->detalleVentas as $detalle) {
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
                                'motivo_movimiento' => "Reversión por anulación de Venta #{$this->record->id} con NC",
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
                            $sunatService = new SunatService();
                            $resultadoEnvio = $sunatService->enviarNotaCredito($nota);

                            if ($resultadoEnvio['success']) {
                                Notification::make()
                                    ->title(' Nota de Crédito Aceptada por SUNAT')
                                    ->body("Se creó y envió exitosamente la Nota de Crédito {$data['serie_nota']}-{$data['numero_nota']} a SUNAT. El inventario ha sido restablecido.")
                                    ->success()
                                    ->duration(10000)
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title(' Nota de Crédito Creada (Error en envío)')
                                    ->body("Se creó la Nota de Crédito {$data['serie_nota']}-{$data['numero_nota']} pero falló el envío a SUNAT: {$resultadoEnvio['message']}")
                                    ->warning()
                                    ->duration(12000)
                                    ->send();
                            }
                        } else {
                            $tipoDoc = strtoupper($comprobante->tipo);
                            $mensaje = " Nota de Crédito {$data['serie_nota']}-{$data['numero_nota']} creada localmente.\n\n";

                            if ($comprobante->tipo === 'boleta') {
                                $mensaje .= " La boleta {$comprobante->serie}-{$comprobante->correlativo} NO ha sido enviada a SUNAT aún .\n\n";
                                $mensaje .= " La boleta ANULADA será excluida automáticamente del próximo Resumen Diario.\n\n";
                            } else {
                                $mensaje .= "El {$tipoDoc} {$comprobante->serie}-{$comprobante->correlativo} no fue enviado a SUNAT.\n\n";
                                $mensaje .= " La Nota de Crédito está lista. Use 'Reenviar' si necesita enviarla después.";
                            }

                            Notification::make()
                                ->title(' Nota de Crédito Creada')
                                ->body($mensaje)
                                ->success()
                                ->duration(12000)
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
                            ->duration(12000)
                            ->send();
                    }

                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error')
                        ->body('Error al crear la nota: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->requiresConfirmation()
            ->modalHeading(function () {
                $comprobante = $this->record->comprobantes()
                    ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                    ->first();
                $tipoComprobante = $comprobante ? $comprobante->tipo : 'ticket';

                if ($tipoComprobante === 'ticket') {
                    return 'Anular Ticket';
                }

                return 'Emitir Nota de Crédito';
            })
            ->modalDescription(function () {
                $comprobante = $this->record->comprobantes()
                    ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                    ->first();
                $tipoComprobante = $comprobante ? $comprobante->tipo : 'ticket';

                if ($tipoComprobante === 'ticket') {
                    return '¿Está seguro de que desea anular este ticket? Esta acción no se puede deshacer.';
                }

                return '¿Está seguro de que desea emitir esta nota de crédito? Se anulará el comprobante original y se restablecerá el inventario.';
            })
            ->modalSubmitActionLabel(fn () => 'Emitir Nota');
        // Agregar botón anular solo si la venta está emitida
        if ($this->record->estado_venta === 'emitida') {
            $comprobante = $this->record->comprobantes()->first();
            $tipoComprobante = $comprobante ? $comprobante->tipo : 'ticket';

            if ($tipoComprobante === 'ticket') {
                // Para tickets: anulación directa desde aquí
                $actions[] = \Filament\Actions\Action::make('anular_ticket')
                    ->label('Anular Ticket')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Anular Ticket')
                    ->modalDescription('¿Está seguro de que desea anular este ticket? Esta acción no se puede deshacer.')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('motivo_anulacion')
                            ->label('Motivo de anulación')
                            ->required()
                            ->maxLength(500)
                            ->placeholder('Ingrese el motivo de la anulación del ticket'),
                    ])
                    ->extraAttributes(['style' => 'min-width:110px; display:inline-flex; align-items:center; justify-content:flex-start; gap:2px; padding-left:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; padding:4px 4px; line-height:1;'])
                    ->action(function (array $data) {
                        // Reusar la lógica centralizada para anular tickets (incluye reversión de inventario)
                        \App\Filament\Resources\Ventas\Tables\VentasTable::anularTicket($this->record, $data['motivo_anulacion'] ?? null);

                        return redirect()->to(VentaResource::getUrl('index'));
                    });
            }
        }

        return $actions;
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function hasUnsavedDataChangesAlert(): bool
    {
        return false;
    }
}
