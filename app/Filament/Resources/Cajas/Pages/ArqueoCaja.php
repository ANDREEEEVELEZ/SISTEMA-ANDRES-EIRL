<?php

namespace App\Filament\Resources\Cajas\Pages;

use App\Filament\Resources\Cajas\CajaResource;
use App\Models\Arqueo;
use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Enums\EstadoArqueo;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ArqueoCaja extends Page implements HasForms
{
    use InteractsWithForms;



    protected static string $resource = CajaResource::class;

    protected string $view = 'filament.resources.cajas.pages.arqueo-caja';

    protected static ?string $title = 'Arqueo de Caja';

    public ?Caja $caja = null;
    public ?Arqueo $arqueo = null;
    public ?float $totalVentas = 0.0;
    public ?float $totalVentasOtrosMedios = 0.0;
    public ?float $totalIngresos = 0.0;
    public ?float $totalEgresos = 0.0;
    public ?float $saldoTeorico = 0.0;

    public ?array $data = [];

    public function mount(): void
    {
        if (!$this->tieneCajaAbierta()) {
            Notification::make()
                ->title('Sin caja abierta')
                ->body('No hay una caja abierta para realizar arqueo. Abra una caja antes de generar arqueos.')
                ->warning()
                ->send();

            $this->redirect(CajaResource::getUrl('index'));
            return;
        }

        // Forzar selección: primero buscar la caja ABIERTA del usuario autenticado.
        // Si no existe y el usuario es super_admin, usar cualquier caja abierta.
        $esSuperAdmin = Auth::check() && optional(Auth::user())->hasRole('super_admin');

            $this->caja = null;
            if ($esSuperAdmin && session('admin_selected_caja_id')) {
                $this->caja = Caja::find(session('admin_selected_caja_id'));
                // Si la caja seleccionada ya no existe o está cerrada, limpiar la sesión
                if (! $this->caja || $this->caja->estado !== 'abierta') {
                    session()->forget('admin_selected_caja_id');
                    $this->caja = null;
                }
            }

            // si aun no hay caja seleccionada, preferir la caja propia del super_admin
            if (! $this->caja && $esSuperAdmin) {
                $this->caja = Caja::where('estado', 'abierta')
                    ->where('user_id', Auth::id())
                    ->orderByDesc('fecha_apertura')
                    ->first();
            }

            // por defecto, buscar caja abierta del usuario autenticado (vendedor)
            if (! $this->caja) {
                $this->caja = Caja::where('estado', 'abierta')
                    ->where('user_id', Auth::id())
                    ->orderByDesc('fecha_apertura')
                    ->first();
            }

        // Log adicional: registrar si la URL pide un arqueo específico
        Log::info('ArqueoCaja: mount params', [
            'query_arqueo_id' => request()->query('arqueo_id'),
            'selected_caja_id' => $this->caja?->id,
        ]);
        // Si se pasa ?arqueo_id= en la URL intentamos cargar ese arqueo (independiente de la caja abierta)
        $requestedId = request()->query('arqueo_id');
        if ($requestedId) {
            $loaded = Arqueo::with('caja')->find($requestedId);
            if ($loaded) {
                // Si el usuario no es super_admin, sólo permitir cargar arqueos relacionados con su propia caja
                if (! (Auth::check() && optional(Auth::user())->hasRole('super_admin'))) {
                    if (! $loaded->caja || ($loaded->caja->user_id ?? null) !== Auth::id()) {
                        Notification::make()
                            ->title('Acceso denegado')
                            ->body('No tiene permiso para ver ese arqueo.')
                            ->danger()
                            ->send();

                        $this->redirect(CajaResource::getUrl('index'));
                        return;
                    }
                }

                $this->arqueo = $loaded;
                // Usar la caja asociada al arqueo para mostrar su información
                $this->caja = $this->arqueo->caja;
                // Rellenar totales desde el arqueo (no recalcular sobre la caja abierta)
                $this->totalVentas = (float) $this->arqueo->total_ventas;
                $this->totalIngresos = (float) $this->arqueo->total_ingresos;
                $this->totalEgresos = (float) $this->arqueo->total_egresos;
                $this->saldoTeorico = (float) $this->arqueo->saldo_teorico;
                $this->data['efectivo_contado'] = $this->arqueo->efectivo_contado;
                $this->data['observacion'] = $this->arqueo->observacion;
                $this->form->fill($this->data);
                return;
            }
            // si no se encuentra el arqueo solicitado, continuamos con el flujo normal
        }

        // Comportamiento por defecto: usar la caja abierta actual y calcular totales
        $this->calcularTotales();

        // Inicializar estado del formulario
        $this->data = [
            'efectivo_contado' => null,
            'observacion' => null,
        ];

        // Si no se solicitó uno específico, cargamos el último arqueo de la caja
        $this->arqueo = Arqueo::where('caja_id', $this->caja->id)
            ->latest()
            ->first();

        if ($this->arqueo) {
            $this->data['efectivo_contado'] = $this->arqueo->efectivo_contado;
            $this->data['observacion'] = $this->arqueo->observacion;
        }

        $this->form->fill($this->data);
    }

    public function isConfirmed(): bool
    {
        if (!$this->arqueo) {
            return false;
        }

        $estado = $this->arqueo->estado;

        // Si el cast produce un enum, comparamos por value; si es string, comparamos directamente
        if (is_object($estado) && property_exists($estado, 'value')) {
            return $estado->value === 'confirmado';
        }

        return $estado === 'confirmado';
    }

    protected function getFormSchema(): array
    {
        return [
            // Header: Información de la Caja
            Placeholder::make('header_caja')
                ->label(new \Illuminate\Support\HtmlString('<span style="font-size: 1.125rem; font-weight: 600; font-family: \'Segoe UI\', system-ui, sans-serif; color: #1e293b;">Información de Caja</span>'))
                ->content(fn () => new \Illuminate\Support\HtmlString(
                    '<div style="background: #1e40af; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem;">
                            <div style="background: rgba(255,255,255,0.2); padding: 0.75rem; border-radius: 0.375rem;">
                                <div style="color: rgba(255,255,255,0.8); font-size: 0.7rem; text-transform: uppercase; margin-bottom: 0.25rem;">Caja</div>
                                <div style="color: white; font-size: 1.125rem; font-weight: bold;">Caja #' . $this->caja->numero_secuencial . '</div>
                            </div>
                            <div style="background: rgba(255,255,255,0.2); padding: 0.75rem; border-radius: 0.375rem;">
                                <div style="color: rgba(255,255,255,0.8); font-size: 0.7rem; text-transform: uppercase; margin-bottom: 0.25rem;">Usuario</div>
                                <div style="color: white; font-size: 0.875rem; font-weight: 600;">' . ($this->caja->user?->name ?? '-') . '</div>
                            </div>
                            <div style="background: rgba(255,255,255,0.2); padding: 0.75rem; border-radius: 0.375rem;">
                                <div style="color: rgba(255,255,255,0.8); font-size: 0.7rem; text-transform: uppercase; margin-bottom: 0.25rem;">Apertura</div>
                                <div style="color: white; font-size: 0.875rem; font-weight: 600;">' . ($this->caja->fecha_apertura?->format('d/m/Y H:i') ?? '-') . '</div>
                            </div>
                            <div style="background: rgba(255,255,255,0.2); padding: 0.75rem; border-radius: 0.375rem;">
                                <div style="color: rgba(255,255,255,0.8); font-size: 0.7rem; text-transform: uppercase; margin-bottom: 0.25rem;">Saldo Inicial</div>
                                <div style="color: white; font-size: 1.125rem; font-weight: bold;">S/ ' . number_format((float) ($this->caja->saldo_inicial ?? 0), 2) . '</div>
                            </div>
                            <div style="background: rgba(255,255,255,0.2); padding: 0.75rem; border-radius: 0.375rem;">
                                <div style="color: rgba(255,255,255,0.8); font-size: 0.7rem; text-transform: uppercase; margin-bottom: 0.25rem;">Cierre</div>
                                <div style="color: white; font-size: 0.875rem; font-weight: 600;">' . now()->format('d/m/Y H:i') . '</div>
                            </div>
                        </div>
                    </div>'
                )),

            // Movimientos del Día
            Placeholder::make('movimientos')
                ->label(new \Illuminate\Support\HtmlString('<span style="font-size: 1.125rem; font-weight: 600; font-family: \'Segoe UI\', system-ui, sans-serif; color: #1e293b;">Movimientos</span>'))
                ->content(fn () => new \Illuminate\Support\HtmlString(
                    '<div style="margin-bottom: 1rem;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem;">
                            <div style="background: #2563eb; padding: 1rem; border-radius: 0.5rem; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <div style="color: rgba(255,255,255,0.9); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Ventas (Efectivo)</div>
                                <div style="color: white; font-size: 1.5rem; font-weight: bold;">S/ ' . number_format($this->totalVentas, 2) . '</div>
                            </div>
                            <div style="background: #1d4ed8; padding: 1rem; border-radius: 0.5rem; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <div style="color: rgba(255,255,255,0.9); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Ingresos</div>
                                <div style="color: white; font-size: 1.5rem; font-weight: bold;">S/ ' . number_format($this->totalIngresos, 2) . '</div>
                            </div>
                            <div style="background: #1e40af; padding: 1rem; border-radius: 0.5rem; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <div style="color: rgba(255,255,255,0.9); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Egresos</div>
                                <div style="color: white; font-size: 1.5rem; font-weight: bold;">S/ ' . number_format($this->totalEgresos, 2) . '</div>
                            </div>
                        </div>
                    </div>'
                )),

            // Saldo Teórico
            Placeholder::make('saldo_teorico')
                ->label(new \Illuminate\Support\HtmlString('<span style="font-size: 1.125rem; font-weight: 600; font-family: \'Segoe UI\', system-ui, sans-serif; color: #1e293b;">Saldo Teórico</span>'))
                ->content(fn () => new \Illuminate\Support\HtmlString(
                    '<div style="background: #3b82f6; padding: 1.25rem; border-radius: 0.5rem; text-align: center; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="color: white; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.5rem; opacity: 0.95;">Saldo Teórico Esperado</div>
                        <div style="color: white; font-size: 2rem; font-weight: bold; margin-bottom: 0.25rem;">S/ ' . number_format($this->saldoTeorico, 2) . '</div>
                        <div style="color: rgba(255,255,255,0.85); font-size: 0.7rem;">Saldo Inicial + Ventas (Efectivo) + Ingresos - Egresos</div>
                    </div>'
                )),

            // Input y Resultado
            TextInput::make('efectivo_contado')
                ->label(new \Illuminate\Support\HtmlString('<span style="font-size: 1.125rem; font-weight: 600; font-family: \'Segoe UI\', system-ui, sans-serif; color: #1e293b;">Efectivo Contado</span>'))
                ->numeric()
                ->step('0.01')
                ->prefix('S/')
                ->placeholder('0.00')
                ->reactive()
                ->columnSpan(1)
                ->disabled(fn () => isset($this->arqueo) && $this->arqueo->estado === EstadoArqueo::CONFIRMADO)
                ->helperText('Ingrese el monto total de efectivo físico contado en caja'),

            Placeholder::make('resultado')
                ->label(new \Illuminate\Support\HtmlString('<span style="font-size: 1.125rem; font-weight: 600; font-family: \'Segoe UI\', system-ui, sans-serif; color: #1e293b;">Resultado del Arqueo</span>'))
                ->content(function () {
                    $efectivo = isset($this->data['efectivo_contado']) && $this->data['efectivo_contado'] !== '' ? (float) $this->data['efectivo_contado'] : null;

                    // Usar las mismas medidas y tipografías que el bloque `saldo_teorico`
                    if (is_null($efectivo)) {
                        return new \Illuminate\Support\HtmlString(
                            '<div style="background: #e5e7eb; padding: 1.25rem; border-radius: 0.5rem; text-align: center; border: 2px dashed #9ca3af; margin-top: 1rem; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">'
                            . '<div style="color: #6b7280; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.5rem; opacity: 0.95;">Esperando conteo...</div>'
                            . '<div style="color: #9ca3af; font-size: 0.7rem;">Ingrese el efectivo para ver el resultado</div>'
                            . '</div>'
                        );
                    }

                    $diferencia = round($efectivo - $this->saldoTeorico, 2);

                    if ($diferencia === 0.0) {
                        $bgColor = '#10b981';
                        $estado = 'CUADRADO';
                        $mensaje = 'No hay diferencia - Arqueo correcto';
                    } elseif ($diferencia > 0) {
                        $bgColor = '#3b82f6';
                        $estado = 'SOBRANTE';
                        $mensaje = 'Hay dinero de más en caja';
                    } else {
                        $bgColor = '#ef4444';
                        $estado = 'FALTANTE';
                        $mensaje = 'Falta dinero en caja';
                    }

                    return new \Illuminate\Support\HtmlString(
                        '<div style="background: ' . $bgColor . '; padding: 1.25rem; border-radius: 0.5rem; text-align: center; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">'
                        . '<div style="color: white; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.5rem; opacity: 0.95;">' . $estado . '</div>'
                        . '<div style="color: white; font-size: 2rem; font-weight: bold; margin-bottom: 0.25rem;">S/ ' . number_format($diferencia, 2) . '</div>'
                        . '<div style="color: rgba(255,255,255,0.85); font-size: 0.7rem;">' . $mensaje . '</div>'
                        . '</div>'
                    );
                }),

            // Observaciones
            Textarea::make('observacion')
                ->label(new \Illuminate\Support\HtmlString('<span style="font-size: 1.125rem; font-weight: 600; font-family: \'Segoe UI\', system-ui, sans-serif; color: #1e293b;">Observaciones</span>'))
                ->rows(3)
                ->maxLength(500)
                ->placeholder('Agregue cualquier observación o nota relevante sobre este arqueo...')
                ->disabled(fn () => isset($this->arqueo) && $this->arqueo->estado === EstadoArqueo::CONFIRMADO),
        ];
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }

    protected function tieneCajaAbierta(): bool
    {
        // Considerar caja abierta pero solamente la(s) caja(s) del usuario actual
        // Si el usuario es super_admin, permitimos ver cualquier caja abierta
        $query = Caja::where('estado', 'abierta');
        if (! (Auth::check() && optional(Auth::user())->hasRole('super_admin'))) {
            $query->where('user_id', Auth::id());
        }

        return $query->exists();
    }

    protected function getCajaAbierta(): ?Caja
    {
        // Retornar la primera caja abierta del usuario (si existe). Super admin puede ver cualquier caja abierta.
        $query = Caja::where('estado', 'abierta')->orderBy('fecha_apertura', 'desc');
        if (! (Auth::check() && optional(Auth::user())->hasRole('super_admin'))) {
            $query->where('user_id', Auth::id());
        }

        return $query->first();
    }

    protected function getInfoCajaAbierta(): string
    {
        $caja = $this->getCajaAbierta();

        if (!$caja) {
            return ' No hay caja abierta';
        }

        return sprintf(
            ' Caja #%d - Abierta el %s - Saldo Inicial: S/ %s',
            $caja->numero_secuencial,
            $caja->fecha_apertura->format('d/m/Y H:i'),
            number_format((float) $caja->saldo_inicial, 2)
        );
    }

    protected function calcularTotales(): void
    {
        $inicio = $this->caja->fecha_apertura;
        $fin = now();

        // Ventas cobradas en efectivo en el periodo

        // Excluir ventas anuladas para mantener coherencia con el cálculo
        // usado en el widget de Apertura/Cierre (calcularSaldoEsperado)
        $this->totalVentas = (float) Venta::where('caja_id', $this->caja->id)
            ->where('metodo_pago', 'efectivo')
            ->where('estado_venta', '!=', 'anulada')
            ->whereDate('fecha_venta', '>=', $inicio->toDateString())
            ->whereDate('fecha_venta', '<=', $fin->toDateString())
            ->sum('total_venta');

        // Ventas por otros medios de pago (tarjeta, yape, transferencia, etc.)
        $this->totalVentasOtrosMedios = (float) Venta::where('caja_id', $this->caja->id)
            ->where('metodo_pago', '!=', 'efectivo')
            ->where('estado_venta', '!=', 'anulada')
            ->whereDate('fecha_venta', '>=', $inicio->toDateString())
            ->whereDate('fecha_venta', '<=', $fin->toDateString())
            ->sum('total_venta');

        // Movimientos de caja
        $this->totalIngresos = (float) MovimientoCaja::where('caja_id', $this->caja->id)
            ->where('tipo', 'ingreso')
            ->whereDate('fecha_movimiento', '>=', $inicio->toDateString())
            ->whereDate('fecha_movimiento', '<=', $fin->toDateString())
            ->sum('monto');

        $this->totalEgresos = (float) MovimientoCaja::where('caja_id', $this->caja->id)
            ->where('tipo', 'egreso')
            ->whereDate('fecha_movimiento', '>=', $inicio->toDateString())
            ->whereDate('fecha_movimiento', '<=', $fin->toDateString())
            ->sum('monto');

        $this->saldoTeorico = (float) $this->caja->saldo_inicial + $this->totalVentas + $this->totalIngresos - $this->totalEgresos;
    }

    public function guardar(): void
    {
        $state = $this->form->getState();

        // Seguridad: asegurar que el usuario sólo pueda guardar arqueos para su propia caja
        if (! (Auth::check() && optional(Auth::user())->hasRole('super_admin'))) {
            if (! $this->caja || ($this->caja->user_id ?? null) !== Auth::id()) {
                Notification::make()->title('Acceso denegado')->body('No tiene permiso para realizar arqueos en esta caja.')->danger()->send();
                return;
            }
        }

        if (!isset($state['efectivo_contado'])) {
            Notification::make()->title('Error')->body('Ingrese el efectivo contado')->danger()->send();
            return;
        }

        $efectivo = (float) $state['efectivo_contado'];

        // Si ya hay un arqueo cargado (por URL o último), y no está confirmado, lo actualizamos
        if ($this->arqueo && ($this->arqueo->estado->value ?? $this->arqueo->estado) !== 'confirmado') {
            $this->arqueo->update([
                'user_id' => Auth::id(),
                'fecha_fin' => now(),
                'efectivo_contado' => $efectivo,
                'diferencia' => round($efectivo - $this->saldoTeorico, 2),
                'estado' => 'pendiente',
                'observacion' => $state['observacion'] ?? $this->arqueo->observacion,
            ]);

            $this->arqueo = $this->arqueo->fresh();
        } else {
            $this->arqueo = Arqueo::create([
                'caja_id' => $this->caja->id,
                'user_id' => Auth::id(),
                'fecha_inicio' => $this->caja->fecha_apertura,
                'fecha_fin' => now(),
                'saldo_inicial' => $this->caja->saldo_inicial,
                'total_ventas' => $this->totalVentas,
                'total_ingresos' => $this->totalIngresos,
                'total_egresos' => $this->totalEgresos,
                'saldo_teorico' => $this->saldoTeorico,
                'efectivo_contado' => $efectivo,
                'diferencia' => round($efectivo - $this->saldoTeorico, 2),
                // guardamos como pendiente (no confirmado) para que el usuario revise antes de confirmar
                'estado' => 'pendiente',
                'observacion' => $state['observacion'] ?? null,
            ]);
        }

        // No generamos PDF al guardar como borrador; permitimos editar hasta confirmar
        $this->data['efectivo_contado'] = $this->arqueo->efectivo_contado;
        $this->data['observacion'] = $this->arqueo->observacion;
        $this->form->fill($this->data);

        Notification::make()
            ->title('Arqueo registrado')
            ->body('El arqueo ha sido guardado como borrador (pendiente).')
            ->success()
            ->send();

        $this->redirect(CajaResource::getUrl('arqueos'));
    }

    /**
     * Confirma el arqueo: crea (o actualiza) el arqueo y lo marca como 'confirmado' en el campo estado.
     * Además, cierra automáticamente la caja con el monto del efectivo contado y copia la observación del arqueo.
     */
    public function confirmar(): void
    {
        $state = $this->form->getState();

        // Seguridad: asegurar que el usuario sólo pueda confirmar arqueos para su propia caja
        if (! (Auth::check() && optional(Auth::user())->hasRole('super_admin'))) {
            if (! $this->caja || ($this->caja->user_id ?? null) !== Auth::id()) {
                Notification::make()->title('Acceso denegado')->body('No tiene permiso para confirmar arqueos en esta caja.')->danger()->send();
                return;
            }
        }

        if (!isset($state['efectivo_contado'])) {
            Notification::make()->title('Error')->body('Ingrese el efectivo contado')->danger()->send();
            return;
        }

        $efectivo = (float) $state['efectivo_contado'];

        // Preferir el arqueo cargado por la propiedad (si se abrió desde la tabla),
        // sino buscar el último arqueo para esta caja
        if (!$this->arqueo) {
            $arqueo = Arqueo::where('caja_id', $this->caja->id)
                ->latest()
                ->first();
        } else {
            $arqueo = $this->arqueo;
        }

        if ($arqueo) {
            // Actualizar y marcar como confirmado
            $arqueo->update([
                'user_id' => Auth::id(),
                'fecha_fin' => now(),
                'efectivo_contado' => $efectivo,
                'diferencia' => round($efectivo - $this->saldoTeorico, 2),
                'observacion' => $state['observacion'] ?? $arqueo->observacion,
                'estado' => 'confirmado',
            ]);
        } else {
            $arqueo = Arqueo::create([
                'caja_id' => $this->caja->id,
                'user_id' => Auth::id(),
                'fecha_inicio' => $this->caja->fecha_apertura,
                'fecha_fin' => now(),
                'saldo_inicial' => $this->caja->saldo_inicial,
                'total_ventas' => $this->totalVentas,
                'total_ingresos' => $this->totalIngresos,
                'total_egresos' => $this->totalEgresos,
                'saldo_teorico' => $this->saldoTeorico,
                'efectivo_contado' => $efectivo,
                'diferencia' => round($efectivo - $this->saldoTeorico, 2),
                'observacion' => $state['observacion'] ?? null,
                'estado' => 'confirmado',
            ]);
        }

        // Actualizar estado local y rellenar formulario en modo bloqueado
        $this->arqueo = $arqueo->fresh();
        $this->data['efectivo_contado'] = $this->arqueo->efectivo_contado;
        $this->data['observacion'] = $this->arqueo->observacion;
        $this->form->fill($this->data);

        // ===== CERRAR LA CAJA AUTOMÁTICAMENTE =====
        // Calcular diferencia entre el efectivo contado y el saldo teórico
        $diferenciaCaja = $efectivo - $this->saldoTeorico;

        // Concatenar observaciones: apertura del arqueo con observación del cierre
        $observacionFinal = $this->caja->observacion ?: '';
        $observacionArqueo = $state['observacion'] ?? '';

        if ($observacionArqueo) {
            $observacionFinal = $observacionFinal
                ? $observacionFinal . ' / ' . $observacionArqueo
                : $observacionArqueo;
        }

        // Cerrar la caja con el monto del efectivo contado
        $this->caja->update([
            'fecha_cierre' => now(),
            'saldo_final' => $efectivo, // El saldo final es el efectivo contado del arqueo
            'diferencia' => $diferenciaCaja,
            'observacion' => $observacionFinal ?: null,
            'estado' => 'cerrada',
        ]);

        Notification::make()
            ->title('Arqueo confirmado y Caja cerrada')
            ->body('El arqueo ha sido confirmado y la caja se ha cerrado automáticamente con S/ ' . number_format($efectivo, 2))
            ->success()
            ->send();

        // Redirigir a la lista de arqueos tras confirmar
        $this->redirect(CajaResource::getUrl('arqueos'));
    }

}
