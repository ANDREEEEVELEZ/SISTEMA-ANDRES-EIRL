<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource;
use App\Filament\Resources\Ventas\Widgets\EstadisticasVentasWidget;
use App\Models\Venta;
use App\Models\SerieComprobante;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ListVentas extends ListRecords
{
    protected static string $resource = VentaResource::class;

    public ?Venta $ventaEncontrada = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('exportar_ventas')
                ->label('Exportar Ventas')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->modal()
                ->modalHeading('Exportar Ventas')
                ->modalWidth('2xl')
                ->form([
                    Forms\Components\Select::make('tipo_comprobante')
                        ->label('Tipo de Comprobante')
                        ->options([
                            'todos' => 'Todos',
                            'ticket' => 'Ticket',
                            'boleta' => 'Boleta',
                            'factura' => 'Factura',
                        ])
                        ->default('todos')
                        ->required(),

                    Forms\Components\DatePicker::make('fecha_inicio')
                        ->label('Fecha Inicio')
                        ->default(now()->startOfMonth())
                        ->required(),

                    Forms\Components\DatePicker::make('fecha_fin')
                        ->label('Fecha Fin')
                        ->default(now())
                        ->required(),

                    Forms\Components\Select::make('tipo_cliente')
                        ->label('Tipo de Cliente')
                        ->options([
                            'todos' => 'Todos',
                            'dni' => 'DNI (Personas)',
                            'ruc' => 'RUC (Empresas)',
                        ])
                        ->default('todos')
                        ->required(),

                    Forms\Components\Select::make('estado_comprobante')
                        ->label('Estado de Comprobante')
                        ->options([
                            'todos' => 'Todos',
                            'emitido' => 'Emitido',
                            'anulado' => 'Anulado',
                            'rechazado' => 'Rechazado',
                        ])
                        ->default('todos')
                        ->required(),
                ])
                ->action(function (array $data) {
                    // Construir URL con parámetros
                    $params = http_build_query([
                        'tipo_comprobante' => $data['tipo_comprobante'],
                        'fecha_inicio' => $data['fecha_inicio'],
                        'fecha_fin' => $data['fecha_fin'],
                        'tipo_cliente' => $data['tipo_cliente'],
                        'estado_comprobante' => $data['estado_comprobante'],
                    ]);

                    // Abrir en nueva pestaña
                    $this->js("window.open('" . route('ventas.export') . "?{$params}', '_blank')");
                })
                ->modalSubmitActionLabel('Exportar'),
            Action::make('anular_comprobante')
                ->label('Emitir nota/Anular comprobante')
                ->color('danger')
                //->icon('heroicon-o-x-circle')
                ->modal()
                ->modalHeading('Emitir nota /Anular comprobante')
                ->modalWidth('3xl')
                ->form([
                    // Paso 1: Seleccionar tipo de comprobante
                    Forms\Components\Select::make('tipo_comprobante')
                        ->label('Tipo de Comprobante')
                        ->options([
                            'ticket' => 'Ticket',
                            'boleta' => 'Boleta',
                            'factura' => 'Factura',
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            // Al cambiar el tipo de comprobante, cargar la serie por defecto (si existe)
                            $serieComprobante = SerieComprobante::where('tipo', $state)
                                ->where('aplica_a', 'ninguno')
                                ->latest('id')
                                ->first();

                            if ($serieComprobante) {
                                $set('serie', $serieComprobante->serie);
                            } else {
                                // Fallbacks comunes
                                if ($state === 'boleta') {
                                    $set('serie', 'B001');
                                } elseif ($state === 'factura') {
                                    $set('serie', 'F001');
                                } elseif ($state === 'ticket') {
                                    // Si no hay serie configurada para ticket, dejar T001 por defecto
                                    $set('serie', 'T001');
                                } else {
                                    $set('serie', null);
                                }
                            }

                            // Si es boleta o factura, establecer tipo_nota por defecto y cargar series de la nota
                            if (in_array($state, ['boleta', 'factura'])) {
                                $set('tipo_nota', 'credito');
                                $this->cargarSeriesAutomaticas('credito', $set, $get);
                            }
                        }),

                    // Paso 2: Seleccionar tipo de nota (solo para boleta/factura)
                    Forms\Components\Select::make('tipo_nota')
                        ->label('Tipo de Nota')
                        ->options([
                            'credito' => 'Nota de Crédito',
                           // 'debito' => 'Nota de Débito',
                        ])
                        ->default('credito')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            $this->cargarSeriesAutomaticas($state, $set, $get);
                        })
                        ->visible(fn (callable $get) => in_array($get('tipo_comprobante'), ['boleta', 'factura'])),

                    // Paso 3: Seleccionar motivo de la nota de crédito (Catálogo 09 SUNAT)
                    Forms\Components\Select::make('codigo_tipo_nota')
                        ->label('Motivo de la Nota de Crédito')
                        ->options([
                            '01' => '01 - Anulación de la Operación',
                            // '02' => '02 - Anulación por Error en el RUC',
                            // '03' => '03 - Corrección por error en la descripción',
                            // '04' => '04 - Descuento Global',
                            // '05' => '05 - Descuento por ítem',
                            // '06' => '06 - Devolución Total',
                            // '07' => '07 - Devolución por ítem',
                            // '08' => '08 - Bonificación',
                            // '09' => '09 - Disminución en el valor',
                            // '13' => '13 - Ajustes - montos y/o fechas de pago',
                        ])
                        ->default('01')
                        ->required()
                        //->helperText('Catálogo 09 SUNAT - Por ahora solo disponible Anulación de la Operación')
                        ->visible(fn (callable $get) => in_array($get('tipo_comprobante'), ['boleta', 'factura'])),

                    // Campos de serie y número de la nota (auto-cargados)
                    Forms\Components\TextInput::make('serie_nota')
                        ->label(fn (callable $get) => 'Serie de la Nota (' . strtoupper($get('tipo_comprobante') ?? '') . ')')
                        ->required()
                        ->disabled()
                        ->dehydrated()
                        ->visible(fn (callable $get) => in_array($get('tipo_comprobante'), ['boleta', 'factura'])),

                    Forms\Components\TextInput::make('numero_nota')
                        ->label('Número de la Nota')
                        ->required()
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->visible(fn (callable $get) => in_array($get('tipo_comprobante'), ['boleta', 'factura'])),

                    // Serie y número del comprobante a anular (auto-cargados con posibilidad de editar)
                    Forms\Components\TextInput::make('serie')
                        ->label(fn (callable $get) => 'Serie de la ' . ucfirst($get('tipo_comprobante') ?? 'Comprobante') . ' a Anular')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $get) {
                            $this->buscarVentaModal($get);
                        })
                        ->visible(fn (callable $get) => !empty($get('tipo_comprobante'))),

                    Forms\Components\TextInput::make('numero')
                        ->label(fn (callable $get) => 'Número de la ' . ucfirst($get('tipo_comprobante') ?? 'Comprobante') . ' a Anular')
                        ->required()
                        ->numeric()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $get) {
                            $this->buscarVentaModal($get);
                        })
                        ->visible(fn (callable $get) => !empty($get('tipo_comprobante'))),

                    // Paso 5: Mostrar detalle del documento referenciado
                    Forms\Components\Placeholder::make('venta_info')
                        ->label('📋 Información del Comprobante')
                        ->content(function () {
                            if (!$this->ventaEncontrada) return '';

                            $comprobante = $this->ventaEncontrada->comprobantes->first();
                            $html = '<div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;">';

                            // Si el comprobante está anulado, mostrar motivo de anulación arriba en destaque
                            if (($comprobante?->estado ?? null) === 'anulado' && !empty($comprobante?->motivo_anulacion)) {
                                $html .= '<div style="background:#fff3f0;border-left:4px solid #ef4444;padding:8px;margin-bottom:10px;border-radius:4px;color:#7f1d1d;">';
                                $html .= '<strong>Motivo de anulación:</strong> ' . e($comprobante->motivo_anulacion);
                                $html .= '</div>';
                            }

                            $html .= '<h4 style="margin: 0 0 10px 0; color: #1f2937;">📋 ' . strtoupper($comprobante?->tipo ?? 'N/A') . ' ' . $comprobante?->serie . '-' . $comprobante?->correlativo . '</h4>';
                            $html .= '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">';
                            $html .= '<div><strong>📅 Fecha:</strong> ' . \Carbon\Carbon::parse($this->ventaEncontrada->fecha_venta)->format('d/m/Y') . '</div>';
                            $html .= '<div><strong>🏷️ Estado:</strong> ' . strtoupper($this->ventaEncontrada->estado_venta) . '</div>';
                            $html .= '<div><strong>👤 Cliente:</strong> ' . ($this->ventaEncontrada->cliente?->nombre_razon ?? $this->ventaEncontrada->nombre_cliente_temporal ?? 'Cliente General') . '</div>';
                            $html .= '<div><strong>🆔 Documento:</strong> ' . ($this->ventaEncontrada->cliente?->num_doc ?? 'S/N') . '</div>';
                            $html .= '</div>';
                            $html .= '<div style="text-align: center; font-size: 18px; font-weight: bold; color: #059669;">💰 Total: S/. ' . number_format((float)$this->ventaEncontrada->total_venta, 2) . '</div>';
                            $html .= '</div>';

                            return new \Illuminate\Support\HtmlString($html);
                        })
                        ->visible(fn () => $this->ventaEncontrada !== null),

                    // Paso 6: Motivo de la nota (solo para boletas y facturas)
                    Forms\Components\Textarea::make('motivo_nota')
                        ->label('Motivo de la Nota')
                        ->required()
                        ->rows(3)
                        ->placeholder('Describe el motivo de la nota de crédito/débito...')
                        ->visible(fn (callable $get) => in_array($get('tipo_comprobante'), ['boleta', 'factura']) && $get('tipo_nota') && $this->ventaEncontrada !== null),

                    // Para tickets pedimos motivo de anulación (solo para tickets)
                    Forms\Components\Textarea::make('motivo_anulacion')
                        ->label('Motivo de Anulación')
                        ->required()
                        ->rows(3)
                        ->placeholder('Describe el motivo de la anulación del ticket...')
                        ->visible(fn (callable $get) => $get('tipo_comprobante') === 'ticket'),
                ])
                ->action(function (array $data) {
                    if ($data['tipo_comprobante'] === 'ticket') {
                        $this->anularTicketModal($data);
                    } else {
                        $this->crearNotaModal($data);
                    }
                })
                ->modalSubmitActionLabel('Procesar Anulación'),
        ];
    }

    protected function buscarVentaModal(callable $get): void
    {
        $tipo = $get('tipo_comprobante');
        $serie = $get('serie');
        $numero = $get('numero');

        if (!$tipo || !$serie || !$numero) {
            $this->ventaEncontrada = null;
            return;
        }

        $venta = Venta::whereHas('comprobantes', function ($query) use ($tipo, $serie, $numero) {
            $query->where('tipo', $tipo)
                  ->where('serie', $serie)
                  ->where('correlativo', $numero);
        })->with(['comprobantes', 'cliente', 'detalleVentas.producto'])->first();

        // Si no se encuentra la venta, mostrar error y mantener modal abierto (no usar halt aquí)
        if (!$venta) {
            $this->ventaEncontrada = null;

            Notification::make()
                ->title('Comprobante no encontrado')
                ->body("No se encontró el {$tipo} {$serie}-{$numero} en el sistema.")
                ->warning()
                ->send();

            // Mostrar error en inputs para que el usuario corrija sin cerrar el modal
            $this->addError('serie', "No se encontró el {$tipo} {$serie}-{$numero}.");
            $this->addError('numero', "No se encontró el {$tipo} {$serie}-{$numero}.");

            $this->dispatch('refresh-form');
            return;
        }

        // Verificar que el usuario tiene permisos sobre esta venta (propietario) a menos que sea super_admin
        $esSuperAdmin = Auth::check() && optional(Auth::user())->hasRole('super_admin');
        if (! $esSuperAdmin && $venta->user_id !== Auth::id()) {
            $this->ventaEncontrada = null;

            Notification::make()
                ->title('Acceso denegado')
                ->danger()
                ->body('No tienes permisos para anular o emitir notas sobre comprobantes de otros usuarios.')
                ->send();

            // Mostrar error en inputs para que el usuario corrija sin cerrar el modal
            $this->addError('serie', "No tienes permisos para acceder al {$tipo} {$serie}-{$numero}.");
            $this->addError('numero', "No tienes permisos para acceder al {$tipo} {$serie}-{$numero}.");

            $this->dispatch('refresh-form');
            return;
        }

        // Validar el estado del comprobante específico
        $comprobante = $venta->comprobantes->first(function ($c) use ($tipo, $serie, $numero) {
            return $c->tipo === $tipo && $c->serie === $serie && $c->correlativo == $numero;
        });

        if ($comprobante && $comprobante->estado === 'anulado') {
            $this->ventaEncontrada = null;

            Notification::make()
                ->title('Comprobante ya anulado')
                ->body("El {$tipo} {$serie}-{$numero} ya está anulado. No se puede volver a anular.")
                ->danger()
                ->send();

            $this->addError('serie', "El {$tipo} {$serie}-{$numero} ya está anulado.");
            $this->addError('numero', "El {$tipo} {$serie}-{$numero} ya está anulado.");

            $this->dispatch('refresh-form');
            return;
        }

        // Validar que la venta NO esté ya anulada
        if ($venta->estado_venta === 'anulada') {
            $this->ventaEncontrada = null;

            Notification::make()
                ->title('Comprobante ya anulado')
                ->body("El {$tipo} {$serie}-{$numero} ya está anulado. No se puede volver a anular.")
                ->danger()
                ->send();

            $this->addError('serie', "El {$tipo} {$serie}-{$numero} ya está anulado.");
            $this->addError('numero', "El {$tipo} {$serie}-{$numero} ya está anulado.");

            $this->dispatch('refresh-form');
            return;
        }

        // Si todo está bien, limpiar errores previos y cargar la venta
        $this->resetErrorBag(['serie', 'numero']);
        $this->ventaEncontrada = $venta;

        // Forzar actualización del formulario
        $this->dispatch('refresh-form');
    }

    public function anularTicketModal(array $data): void
    {
        if (!$this->ventaEncontrada) {
            Notification::make()
                ->title('Error')
                ->body('No se encontró el ticket a anular.')
                ->danger()
                ->send();

            // Evitar que el modal se cierre
            throw new Halt();
        }

        // Verificar permisos: solo el propietario o super_admin puede anular
        $esSuperAdmin = Auth::check() && optional(Auth::user())->hasRole('super_admin');
        if (! $esSuperAdmin && $this->ventaEncontrada->user_id !== Auth::id()) {
            Notification::make()
                ->title('Acceso denegado')
                ->danger()
                ->body('No tienes permisos para anular este ticket.')
                ->send();

            throw new Halt();
        }

        try {
            if ($this->ventaEncontrada->estado_venta === 'anulada') {
                Notification::make()
                    ->title('Error')
                    ->body('Este ticket ya está anulado.')
                    ->danger()
                    ->send();
                return;
            }

            $this->ventaEncontrada->update(['estado_venta' => 'anulada']);

            $comprobante = $this->ventaEncontrada->comprobantes->first();
            if ($comprobante) {
                $comprobante->update([
                    'estado' => 'anulado',
                    'motivo_anulacion' => $data['motivo_anulacion'] ?? null,
                ]);
            }

            // REVERTIR INVENTARIO: Crear movimientos de entrada para devolver el stock
            foreach ($this->ventaEncontrada->detalleVentas as $detalle) {
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
                        'motivo_movimiento' => "Reversión por anulación de Venta #{$this->ventaEncontrada->id}",
                        'fecha_movimiento' => now(),
                    ]);
                }
            }

            Notification::make()
                ->title('Ticket Anulado')
                ->body("El ticket {$comprobante->serie}-{$comprobante->correlativo} ha sido anulado correctamente y el inventario ha sido restablecido.")
                ->success()
                ->send();

            $this->ventaEncontrada = null;

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Error al anular el ticket: ' . $e->getMessage())
                ->danger()
                ->send();
            // Evitar que el modal se cierre en caso de excepción
            throw new Halt();
        }
    }

    /**
     * Limitar los registros visibles según el rol del usuario.
     * - `super_admin` ve todo.
     * - otros roles (p.ej. vendedor) solo ven sus propias ventas (`user_id`).
     */
    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Venta::query();

        if (! Auth::check() || ! Auth::user()->hasRole('super_admin')) {
            $query->where('user_id', Auth::id());
        }

        return $query;
    }

    public function crearNotaModal(array $data): void
    {
        if (!$this->ventaEncontrada) {
            Notification::make()
                ->title('Error')
                ->body('No se encontró el comprobante a anular.')
                ->danger()
                ->send();

            // Evitar que el modal se cierre
            throw new Halt();
        }

        // Verificar permisos: solo el propietario o super_admin puede emitir nota
        $esSuperAdmin = Auth::check() && optional(Auth::user())->hasRole('super_admin');
        if (! $esSuperAdmin && $this->ventaEncontrada->user_id !== Auth::id()) {
            Notification::make()
                ->title('Acceso denegado')
                ->danger()
                ->body('No tienes permisos para emitir notas sobre este comprobante.')
                ->send();

            throw new Halt();
        }

        try {
            if ($this->ventaEncontrada->estado_venta === 'anulada') {
                Notification::make()
                    ->title('Error')
                    ->body('Este comprobante ya está anulado.')
                    ->danger()
                    ->send();
                return;
            }

            $tipoNota = $data['tipo_nota'] === 'credito' ? 'nota de credito' : 'nota de debito';
            $tipoComprobante = $data['tipo_comprobante']; // boleta o factura

            // Buscar el serie_comprobante_id correcto según el tipo de nota y el comprobante
            $serieComprobante = SerieComprobante::where('tipo', $tipoNota)
                ->where('aplica_a', $tipoComprobante)
                ->where('serie', $data['serie_nota'])
                ->first();

            if (!$serieComprobante) {
                Notification::make()
                    ->title('Error')
                    ->body('No se encontró la serie de comprobante configurada para esta nota.')
                    ->danger()
                    ->send();

                // Evitar que el modal se cierre
                throw new Halt();
            }

            $nota = \App\Models\Comprobante::create([
                'venta_id' => $this->ventaEncontrada->id,
                'serie_comprobante_id' => $serieComprobante->id,
                'tipo' => $tipoNota,
                'codigo_tipo_nota' => $data['codigo_tipo_nota'] ?? null, // Catálogo 09 (NC) o 10 (ND) SUNAT
                'serie' => $data['serie_nota'],
                'correlativo' => $data['numero_nota'],
                'fecha_emision' => now(),
                'sub_total' => $this->ventaEncontrada->subtotal_venta,
                'igv' => $this->ventaEncontrada->igv,
                'total' => $this->ventaEncontrada->total_venta,
                'estado' => 'emitido',
                'motivo_anulacion' => $data['motivo_nota'],
            ]);

            // Actualizar el ultimo_numero de la serie
            $serieComprobante->increment('ultimo_numero');

            $this->ventaEncontrada->update(['estado_venta' => 'anulada']);

            $comprobante = $this->ventaEncontrada->comprobantes()
                ->whereNotIn('tipo', ['nota de credito', 'nota de debito'])
                ->first();

            if ($comprobante) {
                $comprobante->update([
                    'estado' => 'anulado',
                    'motivo_anulacion' => "Anulado con " . ucwords(str_replace('_', ' ', $data['tipo_nota'])) . " {$data['serie_nota']}-{$data['numero_nota']}",
                ]);

                // Crear la relación entre el comprobante original y la nota
                \App\Models\ComprobanteRelacion::create([
                    'comprobante_origen_id' => $comprobante->id,
                    'comprobante_relacionado_id' => $nota->id,
                    'tipo_relacion' => $tipoNota,
                ]);
            }

            // REVERTIR INVENTARIO: Crear movimientos de entrada para devolver el stock
            foreach ($this->ventaEncontrada->detalleVentas as $detalle) {
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
                        'motivo_movimiento' => "Reversión por anulación de Venta #{$this->ventaEncontrada->id} con {$tipoNota}",
                        'fecha_movimiento' => now(),
                    ]);
                }
            }

            Notification::make()
                ->title('Nota Creada')
                ->body("Se creó la " . ucwords(str_replace('_', ' ', $data['tipo_nota'])) . " {$data['serie_nota']}-{$data['numero_nota']} para anular el {$comprobante->tipo}. El inventario ha sido restablecido.")
                ->success()
                ->send();

            $this->ventaEncontrada = null;

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Error al crear la nota: ' . $e->getMessage())
                ->danger()
                ->send();
            // Evitar que el modal se cierre en caso de excepción
            throw new Halt();
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EstadisticasVentasWidget::class,
        ];
    }

    protected function cargarSeriesAutomaticas(string $tipoNota, $set, $get): void
    {
        // Obtener el tipo de comprobante (boleta o factura)
        $tipoComprobante = $get('tipo_comprobante');

        // Mapear el tipo de nota al formato de la base de datos
        $tipoNotaDB = $tipoNota === 'credito' ? 'nota de credito' : 'nota de debito';

        // 1. CARGAR LA SERIE DE LA NOTA
        $serieNota = SerieComprobante::where('tipo', $tipoNotaDB)
            ->where('aplica_a', $tipoComprobante)
            ->latest('id')
            ->first();

        if ($serieNota) {
            $set('serie_nota', $serieNota->serie);
            $set('numero_nota', $serieNota->ultimo_numero + 1);
        } else {
            // Valor por defecto según el tipo
            $serieDefault = $tipoComprobante === 'boleta'
                ? ($tipoNota === 'credito' ? 'BC01' : 'BD01')
                : ($tipoNota === 'credito' ? 'FC01' : 'FD01');

            $set('serie_nota', $serieDefault);
            $set('numero_nota', 1);
        }

        // 2. CARGAR LA SERIE DEL COMPROBANTE A ANULAR (boleta o factura)
        $serieComprobante = SerieComprobante::where('tipo', $tipoComprobante)
            ->where('aplica_a', 'ninguno')
            ->latest('id')
            ->first();

        if ($serieComprobante) {
            $set('serie', $serieComprobante->serie);
        } else {
            // Valor por defecto
            $set('serie', $tipoComprobante === 'boleta' ? 'B001' : 'F001');
        }

        Log::info('Series cargadas automáticamente', [
            'tipoComprobante' => $tipoComprobante,
            'tipoNota' => $tipoNotaDB,
            'serie_nota' => $get('serie_nota'),
            'numero_nota' => $get('numero_nota'),
            'serie_comprobante' => $get('serie'),
        ]);
    }

    // Mantener el método anterior por compatibilidad
    protected function cargarSerieYNumeroNota(string $tipoNota, $set, $get): void
    {
        $this->cargarSeriesAutomaticas($tipoNota, $set, $get);
    }
}
