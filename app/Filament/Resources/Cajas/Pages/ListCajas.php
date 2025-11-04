<?php

namespace App\Filament\Resources\Cajas\Pages;

use App\Filament\Resources\Cajas\CajaResource;
use App\Filament\Resources\Cajas\Widgets\AperturaCierreWidget;
use App\Filament\Resources\Cajas\Widgets\MovimientosCajaTable;
use App\Filament\Resources\Cajas\Widgets\TotalesCajaWidget;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;
use App\Models\Caja;
use App\Models\Venta;
use App\Models\MovimientoCaja;
use Illuminate\Support\Carbon;


class ListCajas extends ListRecords
{
    protected static string $resource = CajaResource::class;


    protected function getHeaderWidgets(): array
    {
        return [
            TotalesCajaWidget::class,
            AperturaCierreWidget::class,
            MovimientosCajaTable::class,
        ];
    }

    /**
     * Forzar orden por fecha_apertura descendente en la query de la tabla.
     */
    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation|null
    {
        // Usar query Eloquent del modelo para asegurar el tipo correcto y forzar el orden por fecha
        return Caja::query()->orderBy('fecha_apertura', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('registrarMovimiento')
                ->label('Registrar movimiento')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(CajaResource::getUrl('registrar-movimiento')),

            Action::make('reporteArqueo')
                ->label('Reportes de arqueo')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->url(CajaResource::getUrl('arqueos')),

            Action::make('exportarInformacion')
                ->label('Exportar información')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->modalHeading('Exportar información de cajas')
                ->modalWidth('lg')
                ->form([
                    Checkbox::make('seleccionar_caja')
                        ->label('Seleccionar caja específica')
                        ->reactive()
                        ->default(false)
                        ->afterStateUpdated(function ($state, callable $set) {
                            // Si el usuario desmarca la selección de caja, limpiar caja_id
                            if (! $state) {
                                $set('caja_id', null);
                            }
                        }),

                    Select::make('caja_id')
                        ->label('Caja (opcional)')
                        ->placeholder('Todas las cajas')
                        ->reactive()
                        ->visible(fn ($get) => (bool) $get('seleccionar_caja'))
                        ->searchable()
                        ->afterStateUpdated(function ($state, callable $set) {
                            // Cuando se selecciona una caja, auto-llenar rango de fechas con la apertura/cierre de la caja
                            if ($state) {
                                $caja = \App\Models\Caja::find($state);
                                if ($caja) {
                                    $inicio = $caja->fecha_apertura ? $caja->fecha_apertura->toDateString() : null;
                                    // Para export por caja específica usamos solamente la fecha de apertura
                                    // (no queremos mostrar el periodo hasta la fecha de cierre)
                                    $fin = $inicio;
                                    if ($inicio) {
                                        $set('fecha_inicio', $inicio);
                                    }
                                    if ($fin) {
                                        $set('fecha_fin', $fin);
                                    }
                                }
                            }
                        })
                        ->options(function ($get) {
                            // Si no hay rango aún, devolver vacío; el usuario selecciona fechas primero
                            $map = [];
                            if ($get('fecha_inicio') && $get('fecha_fin')) {
                                $inicio = \Carbon\Carbon::parse($get('fecha_inicio'))->startOfDay();
                                $fin = \Carbon\Carbon::parse($get('fecha_fin'))->endOfDay();
                                $items = Caja::whereBetween('fecha_apertura', [$inicio, $fin])
                                    ->orWhereHas('movimientosCaja', function ($q) use ($inicio, $fin) {
                                        $q->whereBetween('fecha_movimiento', [$inicio, $fin]);
                                    })
                                    ->orderByDesc('fecha_apertura')
                                    ->get();

                                $map = $items->mapWithKeys(function ($c) {
                                    $label = $c->fecha_apertura ? $c->fecha_apertura->format('d/m/Y H:i') : ('Caja #' . $c->numero_secuencial);
                                    return [$c->id => $label];
                                })->toArray();
                            }

                            // Si hay un valor seleccionado que no está en el mapa (por ejemplo
                            // porque se eligió antes de cambiar el rango), incluirlo para evitar
                            // el error "The selected ... is invalid." y mostrar mensajes en SPA.
                            $selected = $get('caja_id');
                            if ($selected && ! array_key_exists($selected, $map)) {
                                $c = Caja::find($selected);
                                if ($c) {
                                    $map[$c->id] = $c->fecha_apertura ? $c->fecha_apertura->format('d/m/Y H:i') : ('Caja #' . $c->numero_secuencial);
                                }
                            }

                            return $map;
                        }),

                    DatePicker::make('fecha_inicio')
                        ->label('Fecha inicio')
                        ->required()
                        ->reactive()
                        ->default(now()->startOfMonth())
                        ->disabled(fn ($get) => (bool) $get('seleccionar_caja') && (bool) $get('caja_id')),

                    DatePicker::make('fecha_fin')
                        ->label('Fecha fin')
                        ->required()
                        ->reactive()
                        ->default(now())
                        ->disabled(fn ($get) => (bool) $get('seleccionar_caja') && (bool) $get('caja_id')),

                    Checkbox::make('incluir_resumen')
                        ->label('Incluir resumen de caja')
                        ->default(true),

                    Checkbox::make('incluir_movimientos')
                        ->label('Incluir movimientos de caja')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    // Si el usuario seleccionó una caja específica, no exigir fechas.
                    $inicio = null;
                    $fin = null;
                    if (!empty($data['seleccionar_caja'])) {
                        if (empty($data['caja_id'])) {
                            Notification::make()->title('Caja no seleccionada')->danger()->body('Seleccione la caja específica o desmarque la opción.')->send();
                            return null;
                        }
                        // Usaremos las fechas de la propia caja en el servidor; aquí no necesitamos calcularlas.
                    } else {
                        // Validaciones básicas cuando NO se selecciona una caja: fechas obligatorias
                        if (empty($data['fecha_inicio']) || empty($data['fecha_fin'])) {
                            Notification::make()->title('Fechas incompletas')->danger()->body('Seleccione fecha inicio y fin.')->send();
                            return null;
                        }

                        $inicio = Carbon::parse($data['fecha_inicio'])->startOfDay();
                        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();

                        // Límite recomendado de 90 días para evitar timeouts
                        if ($inicio->diffInDays($fin) > 90) {
                            Notification::make()
                                ->title('Rango muy amplio')
                                ->danger()
                                ->body('El rango seleccionado excede los 90 días. Seleccione un rango menor o use exportación por lotes.')
                                ->send();
                            return null;
                        }
                    }

                    // Determinar las cajas a incluir según la selección
                    if (!empty($data['seleccionar_caja']) && !empty($data['caja_id'])) {
                        $cajas = Caja::where('id', $data['caja_id'])->get();
                    } else {
                        // Todas las cajas que tuvieron apertura en el rango o movimientos en el rango
                        $cajas = Caja::whereBetween('fecha_apertura', [$inicio, $fin])
                            ->orWhereHas('movimientosCaja', function ($q) use ($inicio, $fin) {
                                $q->whereBetween('fecha_movimiento', [$inicio, $fin]);
                            })
                            ->orderByDesc('fecha_apertura')
                            ->get();
                    }

                    if ($cajas->isEmpty()) {
                        Notification::make()->title('Sin datos')->warning()->body('No se encontraron cajas en el rango seleccionado.')->send();
                        return null;
                    }

                    $reportes = [];
                    foreach ($cajas as $caja) {
                        $totalVentas = (float) Venta::where('caja_id', $caja->id)
                            ->where('metodo_pago', 'efectivo')
                            ->whereBetween('fecha_venta', [$inicio, $fin])
                            ->sum('total_venta');

                        $totalIngresos = (float) MovimientoCaja::where('caja_id', $caja->id)
                            ->where('tipo', 'ingreso')
                            ->whereBetween('fecha_movimiento', [$inicio, $fin])
                            ->sum('monto');

                        $totalEgresos = (float) MovimientoCaja::where('caja_id', $caja->id)
                            ->where('tipo', 'egreso')
                            ->whereBetween('fecha_movimiento', [$inicio, $fin])
                            ->sum('monto');

                        $movimientos = [];
                        if (!empty($data['incluir_movimientos'])) {
                            $movimientos = MovimientoCaja::where('caja_id', $caja->id)
                                ->whereBetween('fecha_movimiento', [$inicio, $fin])
                                ->orderBy('fecha_movimiento')
                                ->get();
                        }

                        $reportes[] = [
                            'caja' => $caja,
                            'total_ventas' => $totalVentas,
                            'total_ingresos' => $totalIngresos,
                            'total_egresos' => $totalEgresos,
                            'movimientos' => $movimientos,
                        ];
                    }

                    // Redirigir a la ruta que genera el PDF (GET) para que el navegador reciba el stream directamente
                    $params = [
                        'incluir_resumen' => !empty($data['incluir_resumen']) ? 1 : 0,
                        'incluir_movimientos' => !empty($data['incluir_movimientos']) ? 1 : 0,
                    ];

                    // Si la selección fue por caja específica, enviar sólo caja_id; el servidor
                    // determinará las fechas según la caja. Si no, enviar el rango para el export.
                    if (!empty($data['seleccionar_caja']) && !empty($data['caja_id'])) {
                        $params['caja_id'] = $data['caja_id'];
                    } else {
                        $params['fecha_inicio'] = $inicio->toDateString();
                        $params['fecha_fin'] = $fin->toDateString();
                    }

                    // Intentar forzar descarga automática en el navegador (evita pop-ups)
                    $params['download'] = 1;
                    return redirect()->to(route('reportes.cajas_export', $params));
                }),
        ];
    }
    protected static int $headerWidgetsColumns = 2;
}

