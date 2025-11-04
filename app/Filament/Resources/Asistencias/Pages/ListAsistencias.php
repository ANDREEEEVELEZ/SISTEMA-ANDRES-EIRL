<?php

namespace App\Filament\Resources\Asistencias\Pages;

use App\Filament\Resources\Asistencias\AsistenciaResource;
use App\Models\Asistencia;
use App\Models\Empleado;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListAsistencias extends ListRecords
{
    protected static string $resource = AsistenciaResource::class;

    public $mesActual;
    public $anioActual;
    public $empleadoSeleccionado = null;

    public function mount(): void
    {
        parent::mount();
        
        $this->mesActual = now()->month;
        $this->anioActual = now()->year;
        
        // Si no es super_admin, seleccionar el empleado del usuario actual automáticamente
        if (!Auth::user()->hasRole('super_admin')) {
            $empleado = Auth::user()->empleado;
            if ($empleado) {
                $this->empleadoSeleccionado = $empleado->id;
            }
        }
    }

    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getTableQuery();
        
        // Si hay un empleado seleccionado, filtrar por ese empleado
        if ($this->empleadoSeleccionado) {
            $query->where('empleado_id', $this->empleadoSeleccionado);
        }
        
        return $query;
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            CreateAction::make(),
        ];
        
        // Solo super_admin puede generar reportes
        if (Auth::user()->hasRole('super_admin')) {
            $actions[] = Action::make('generar_reporte')
                ->label('Generar Reporte')
                ->icon('heroicon-o-document-chart-bar')
                ->color('info')
                ->modalHeading('Generar Reporte de Asistencia')
                ->modalSubmitActionLabel('Descargar PDF')
                ->modalWidth('lg')
                ->form([
                    Radio::make('tipo_reporte')
                        ->label('Tipo de Reporte')
                        ->options([
                            'individual' => 'Individual (Un trabajador)',
                            'general' => 'General (Todos los trabajadores)',
                        ])
                        ->default('individual')
                        ->required()
                        ->live()
                        ->columnSpanFull(),
                    
                    Select::make('empleado_id')
                        ->label('Seleccionar Trabajador')
                        ->options(function () {
                            return Empleado::where('estado_empleado', 'activo')
                                ->orderBy('nombres')
                                ->get()
                                ->mapWithKeys(function ($empleado) {
                                    return [$empleado->id => $empleado->nombre_completo];
                                });
                        })
                        ->searchable()
                        ->required(fn ($get) => $get('tipo_reporte') === 'individual')
                        ->visible(fn ($get) => $get('tipo_reporte') === 'individual')
                        ->columnSpanFull(),
                    
                    DatePicker::make('fecha_inicio')
                        ->label('Fecha Inicio')
                        ->default(now()->startOfMonth())
                        ->required()
                        ->maxDate(now())
                        ->native(false)
                        ->displayFormat('d/m/Y'),
                    
                    DatePicker::make('fecha_fin')
                        ->label('Fecha Fin')
                        ->default(now())
                        ->required()
                        ->maxDate(now())
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->afterOrEqual('fecha_inicio'),
                    
                    Checkbox::make('incluir_resumen')
                        ->label('Resumen estadístico')
                        ->default(true)
                        ->inline(false),
                    
                    Checkbox::make('incluir_detalle')
                        ->label('Listado de días trabajados')
                        ->helperText('Solo muestra los días con asistencia registrada')
                        ->default(true)
                        ->inline(false),
                    
                    Checkbox::make('incluir_observaciones')
                        ->label('Mostrar observaciones')
                        ->default(true)
                        ->inline(false),
                    
                    Checkbox::make('incluir_metodo')
                        ->label('Mostrar método de registro')
                        ->default(true)
                        ->inline(false),
                ])
                ->action(function (array $data) {
                    // Construir la URL con los parámetros
                    $params = http_build_query([
                        'tipo_reporte' => $data['tipo_reporte'],
                        'empleado_id' => $data['empleado_id'] ?? null,
                        'fecha_inicio' => $data['fecha_inicio'],
                        'fecha_fin' => $data['fecha_fin'],
                        'incluir_resumen' => $data['incluir_resumen'] ?? false,
                        'incluir_detalle' => $data['incluir_detalle'] ?? false,
                        'incluir_observaciones' => $data['incluir_observaciones'] ?? false,
                        'incluir_metodo' => $data['incluir_metodo'] ?? false,
                    ]);
                    
                    $url = route('reportes.asistencias.pdf') . '?' . $params;
                    
                    // Descargar PDF sin abrir nueva pestaña usando iframe oculto
                    $this->js("
                        const link = document.createElement('a');
                        link.href = '$url';
                        link.download = '';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    ");
                });
        }
        
        return $actions;
    }

    public function getHeader(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.resources.asistencias.pages.list-asistencias-header');
    }

    public function mesAnterior()
    {
        $fecha = Carbon::create($this->anioActual, $this->mesActual, 1)->subMonth();
        $this->mesActual = $fecha->month;
        $this->anioActual = $fecha->year;
    }

    public function mesSiguiente()
    {
        $fecha = Carbon::create($this->anioActual, $this->mesActual, 1)->addMonth();
        $this->mesActual = $fecha->month;
        $this->anioActual = $fecha->year;
    }

    public function updatedEmpleadoSeleccionado()
    {
        // Resetear el mes al mes actual cuando se cambia de empleado
        $this->mesActual = now()->month;
        $this->anioActual = now()->year;
    }

    public function getCalendarioDataProperty()
    {
        $fecha = Carbon::create($this->anioActual, $this->mesActual, 1);
        $diasEnMes = $fecha->daysInMonth;
        $primerDiaSemana = $fecha->dayOfWeek; // 0 = Domingo, 6 = Sábado (esto es correcto ahora)
        
        // Obtener asistencias del mes
        $asistencias = [];
        if ($this->empleadoSeleccionado) {
            $asistencias = Asistencia::where('empleado_id', $this->empleadoSeleccionado)
                ->whereMonth('fecha', $this->mesActual)
                ->whereYear('fecha', $this->anioActual)
                ->get()
                ->keyBy(function($item) {
                    return Carbon::parse($item->fecha)->day;
                });
        }

        return [
            'diasEnMes' => $diasEnMes,
            'primerDiaSemana' => $primerDiaSemana,
            'asistencias' => $asistencias,
            'nombreMes' => $fecha->locale('es')->translatedFormat('F Y'),
        ];
    }

    public function getEmpleadosProperty()
    {
        if (Auth::user()->hasRole('super_admin')) {
            return Empleado::where('estado_empleado', 'activo')
                ->orderBy('nombres')
                ->get();
        }
        return collect([]);
    }

    public function getEmpleadoSeleccionadoDataProperty()
    {
        if ($this->empleadoSeleccionado) {
            return Empleado::find($this->empleadoSeleccionado);
        }
        
        // Si no es super_admin, devolver el empleado del usuario actual
        if (!Auth::user()->hasRole('super_admin')) {
            return Auth::user()->empleado;
        }
        
        return null;
    }

    public function getEstadoEmpleadoProperty()
    {
        if (!$this->empleadoSeleccionado) {
            return [
                'estado' => 'Sin datos',
                'color' => '#9ca3af',
                'icono' => '⚫'
            ];
        }

        // Buscar asistencia de HOY
        $asistenciaHoy = Asistencia::where('empleado_id', $this->empleadoSeleccionado)
            ->whereDate('fecha', Carbon::today())
            ->first();

        // Determinar estado
        if (!$asistenciaHoy) {
            // No ha registrado entrada hoy
            return [
                'estado' => 'Fuera',
                'color' => '#ef4444',
                'icono' => '🔴'
            ];
        } elseif ($asistenciaHoy->hora_entrada && !$asistenciaHoy->hora_salida) {
            // Tiene entrada pero no salida (está trabajando)
            return [
                'estado' => 'Trabajando',
                'color' => '#22c55e',
                'icono' => '🟢'
            ];
        } else {
            // Tiene entrada y salida (terminó su jornada)
            return [
                'estado' => 'Fuera',
                'color' => '#ef4444',
                'icono' => '🔴'
            ];
        }
    }

    public function getUltimoRegistroProperty()
    {
        if (!$this->empleadoSeleccionado) {
            return 'Sin datos';
        }

        // Buscar la última asistencia registrada
        $ultimaAsistencia = Asistencia::where('empleado_id', $this->empleadoSeleccionado)
            ->orderBy('fecha', 'desc')
            ->orderBy('updated_at', 'desc')
            ->first();

        if (!$ultimaAsistencia) {
            return 'Sin registros';
        }

        // Determinar cuál fue el último registro (salida o entrada)
        $ultimaHora = $ultimaAsistencia->hora_salida ?? $ultimaAsistencia->hora_entrada;
        $fecha = Carbon::parse($ultimaAsistencia->fecha);

        if ($fecha->isToday()) {
            return 'Hoy, ' . Carbon::parse($ultimaHora)->format('H:i');
        } elseif ($fecha->isYesterday()) {
            return 'Ayer, ' . Carbon::parse($ultimaHora)->format('H:i');
        } else {
            return $fecha->format('d/m/Y') . ', ' . Carbon::parse($ultimaHora)->format('H:i');
        }
    }

    public function getEstadoDiaProperty($dia)
    {
        $calendarioData = $this->calendarioData;
        if (isset($calendarioData['asistencias'][$dia])) {
            return $calendarioData['asistencias'][$dia]->estado;
        }
        return null;
    }
}
