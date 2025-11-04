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

class ArqueoCaja extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = CajaResource::class;

    protected string $view = 'filament.resources.cajas.pages.arqueo-caja';

    protected static ?string $title = 'Arqueo de Caja';

    public ?Caja $caja = null;
    public ?Arqueo $arqueo = null;
    public ?float $totalVentas = 0.0;
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

        $this->caja = $this->getCajaAbierta();
        // Si se pasa ?arqueo_id= en la URL intentamos cargar ese arqueo (independiente de la caja abierta)
        $requestedId = request()->query('arqueo_id');
        if ($requestedId) {
            $loaded = Arqueo::with('caja')->find($requestedId);
            if ($loaded) {
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
            Placeholder::make('caja_info')
                ->label('Caja')
                ->content(fn () => new \Illuminate\Support\HtmlString(
                    sprintf('<div class="text-sm">Caja #%d — <strong>Abierta:</strong> %s</div><div class="text-sm">Saldo Inicial: <strong>S/ %s</strong></div>',
                        $this->caja->numero_secuencial,
                        $this->caja->fecha_apertura?->format('d/m/Y H:i') ?? '-',
                        number_format((float) ($this->caja->saldo_inicial ?? 0), 2)
                    )
                )),

            Placeholder::make('periodo')
                ->label('Periodo')
                ->content(fn () => new \Illuminate\Support\HtmlString(
                    sprintf('<div class="text-sm"><strong>Inicio:</strong> %s</div><div class="text-sm"><strong>Fin:</strong> %s</div>',
                        $this->caja->fecha_apertura?->format('d/m/Y H:i') ?? '-',
                        now()->format('d/m/Y H:i')
                    )
                )),

            // Nota: mostramos saldo teórico una sola vez (resumen), no duplicar en el formulario

            Placeholder::make('total_ventas')
                ->label('Total ventas (efectivo)')
                ->content(fn () => new \Illuminate\Support\HtmlString('<div class="text-lg font-semibold">S/ ' . number_format($this->totalVentas, 2) . '</div>')),

            Placeholder::make('total_ingresos')
                ->label('Total ingresos')
                ->content(fn () => new \Illuminate\Support\HtmlString('<div class="text-lg font-semibold">S/ ' . number_format($this->totalIngresos, 2) . '</div>')),

            Placeholder::make('total_egresos')
                ->label('Total egresos')
                ->content(fn () => new \Illuminate\Support\HtmlString('<div class="text-lg font-semibold">S/ ' . number_format($this->totalEgresos, 2) . '</div>')),

            TextInput::make('efectivo_contado')
                ->label('Efectivo contado (S/)')
                ->numeric()
                ->step('0.01')
                ->prefix('S/')
                ->reactive()
                // No necesitamos re-escribir el estado manualmente en afterStateUpdated;
                // dejar que Filament maneje el binding evita perder dígitos en el input.
                ->disabled(fn () => isset($this->arqueo) && $this->arqueo->estado === EstadoArqueo::CONFIRMADO),

            Placeholder::make('saldo_teorico_block')
                ->label('Saldo teórico')
                ->content(fn () => new \Illuminate\Support\HtmlString('<div class="text-xl font-semibold">S/ ' . number_format($this->saldoTeorico, 2) . '</div>')),

            Textarea::make('observacion')
                ->label('Observación')
                ->rows(3)
                ->maxLength(500)
                ->disabled(fn () => isset($this->arqueo) && $this->arqueo->estado === EstadoArqueo::CONFIRMADO),

            Placeholder::make('resultado')
                ->label('Resultado')
                ->content(function () {
                    $efectivo = isset($this->data['efectivo_contado']) && $this->data['efectivo_contado'] !== '' ? (float) $this->data['efectivo_contado'] : null;
                    if (is_null($efectivo)) {
                        return new \Illuminate\Support\HtmlString('<div class="text-sm text-gray-500">Ingrese el efectivo contado para ver la diferencia.</div>');
                    }

                    $diferencia = round($efectivo - $this->saldoTeorico, 2);
                    $clase = $diferencia === 0 ? 'text-green-600' : ($diferencia > 0 ? 'text-blue-600' : 'text-red-600');

                    return new \Illuminate\Support\HtmlString(sprintf('<div class="text-sm">Diferencia</div><div class="text-xl font-semibold %s">S/ %s</div><div class="text-xs text-gray-500 mt-1">%s</div>',
                        $clase,
                        number_format($diferencia, 2),
                        $diferencia === 0 ? 'Cuadrado (no hay diferencia)' : ($diferencia > 0 ? 'Sobrante' : 'Faltante')
                    ));
                }),
        ];
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }

    protected function tieneCajaAbierta(): bool
    {
        // Considerar caja abierta si existe cualquier registro con estado 'abierta'
        // (no filtrar por fecha para evitar falsos negativos por diferencias de zona/fecha)
        return Caja::where('estado', 'abierta')->exists();
    }

    protected function getCajaAbierta(): ?Caja
    {
        // Retornar la primera caja abierta (si existe). No filtramos por fecha.
        return Caja::where('estado', 'abierta')->first();
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
        $this->totalVentas = (float) Venta::where('caja_id', $this->caja->id)
            ->where('metodo_pago', 'efectivo')
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
     */
    public function confirmar(): void
    {
        $state = $this->form->getState();

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

        // Generar PDF y guardarlo en storage/app/public/arqueos (reutilizamos código)
        $pdf = Pdf::loadView('reportes.arqueo', ['arqueo' => $arqueo->fresh()]);

        $folder = storage_path('app/public/arqueos');
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $filename = sprintf('arqueo_caja_%d_%s.pdf', $arqueo->caja_id, $arqueo->created_at->format('Ymd_His'));
        $path = $folder . DIRECTORY_SEPARATOR . $filename;
        $pdf->save($path);

    // Nota: no guardamos la ruta del PDF en la base de datos por decisión del sistema

        // Actualizar estado local y rellenar formulario en modo bloqueado
        $this->arqueo = $arqueo->fresh();
        $this->data['efectivo_contado'] = $this->arqueo->efectivo_contado;
        $this->data['observacion'] = $this->arqueo->observacion;
        $this->form->fill($this->data);

        Notification::make()
            ->title('Arqueo confirmado')
            ->body('El arqueo ha sido confirmado y ahora puede cerrar la caja.')
            ->success()
            ->send();

        $this->redirect(CajaResource::getUrl('arqueos'));
    }
}
