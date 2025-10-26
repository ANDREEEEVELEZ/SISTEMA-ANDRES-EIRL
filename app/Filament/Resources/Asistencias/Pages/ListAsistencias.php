<?php

namespace App\Filament\Resources\Asistencias\Pages;

use App\Filament\Resources\Asistencias\AsistenciaResource;
use App\Models\Asistencia;
use App\Models\Empleado;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
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
        return [
            CreateAction::make(),
        ];
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
