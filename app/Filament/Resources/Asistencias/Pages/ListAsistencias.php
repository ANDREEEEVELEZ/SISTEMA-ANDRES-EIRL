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
        
        // Si no es super_admin, seleccionar el empleado del usuario actual
        if (!Auth::user()->hasRole('super_admin')) {
            $this->empleadoSeleccionado = Auth::user()->empleado?->id;
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

    public function getEstadoDiaProperty($dia)
    {
        $calendarioData = $this->calendarioData;
        if (isset($calendarioData['asistencias'][$dia])) {
            return $calendarioData['asistencias'][$dia]->estado;
        }
        return null;
    }
}
