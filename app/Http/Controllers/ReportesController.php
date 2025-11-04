<?php

namespace App\Http\Controllers;

use App\Models\Arqueo;
use App\Models\Asistencia;
use App\Models\Caja;
use App\Models\Empleado;
use App\Models\MovimientoCaja;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportesController extends Controller
{
    /**
     * Genera y descarga/visualiza el PDF de un arqueo existente
     */
    public function arqueoPdf($id)
    {
        $arqueo = Arqueo::with(['caja', 'user'])->findOrFail($id);

        $pdf = Pdf::loadView('reportes.arqueo', compact('arqueo'));

        $filename = sprintf('arqueo_caja_%d_%s.pdf', $arqueo->caja_id, $arqueo->created_at->format('Ymd_His'));

        return $pdf->stream($filename);
    }

    /**
     * Exporta PDF de cajas según filtros (fecha inicio/fin, caja_id, incluir_resumen, incluir_movimientos)
     */
    public function cajasExport(Request $request)
    {
        // Soportar dos modos:
        // 1) Export por caja específica: se envía caja_id y no se requieren fechas. Usar fechas de la caja.
        // 2) Export por rango: se envían fecha_inicio y fecha_fin.
        $inicio = null;
        $fin = null;

        if ($request->has('caja_id') && $request->query('caja_id')) {
            $caja = Caja::find($request->query('caja_id'));
            if (! $caja) {
                abort(404, 'Caja no encontrada.');
            }

            $inicio = $caja->fecha_apertura ? $caja->fecha_apertura->startOfDay() : \Carbon\Carbon::now()->startOfDay();
            // Usar la fecha de apertura como inicio y fin para que el reporte muestre una sola fecha
            $fin = $caja->fecha_apertura ? $caja->fecha_apertura->endOfDay() : $inicio;
        } else {
            if (! $request->query('fecha_inicio') || ! $request->query('fecha_fin')) {
                abort(400, 'fecha_inicio y fecha_fin son requeridas cuando no se selecciona una caja específica.');
            }

            $inicio = \Carbon\Carbon::parse($request->query('fecha_inicio'))->startOfDay();
            $fin = \Carbon\Carbon::parse($request->query('fecha_fin'))->endOfDay();
        }

        // Si se proporciona caja_id, usar solo esa caja
        if ($request->has('caja_id') && $request->query('caja_id')) {
            $cajas = Caja::where('id', $request->query('caja_id'))->get();
        } else {
            $cajas = Caja::whereBetween('fecha_apertura', [$inicio, $fin])
                ->orWhereHas('movimientosCaja', function ($q) use ($inicio, $fin) {
                    $q->whereBetween('fecha_movimiento', [$inicio, $fin]);
                })
                ->orderByDesc('fecha_apertura')
                ->get();
        }

        if ($cajas->isEmpty()) {
            abort(404, 'No se encontraron cajas para el rango seleccionado.');
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
            if ($request->boolean('incluir_movimientos')) {
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

        $pdf = Pdf::loadView('reportes.cajas_export', [
            'reportes' => $reportes,
            'inicio' => $inicio,
            'fin' => $fin,
            'incluir_resumen' => $request->boolean('incluir_resumen'),
            'incluir_movimientos' => $request->boolean('incluir_movimientos'),
        ]);

        $filename = sprintf('export_cajas_%s_%s.pdf', $inicio->format('Ymd'), $fin->format('Ymd'));

        // Si se solicita descarga forzada, usar download() para que el navegador
        // inicie la descarga automáticamente. En caso contrario, hacemos stream.
        if ($request->query('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Página intermedia que abre la ruta de exportación del PDF en una nueva pestaña
     * y redirige de vuelta al usuario.
     */
    public function cajasOpen(Request $request)
    {
        $params = $request->query();
        $pdfUrl = route('reportes.cajas_export', $params);

        // Página simple que abre el PDF en una nueva pestaña y vuelve atrás
        return response()->view('reportes.open_pdf', ['pdfUrl' => $pdfUrl]);
    }

    /**
     * Genera PDF de reporte de asistencias
     */
    public function asistenciasPdf(Request $request)
    {
        // Validar parámetros
        $tipoReporte = $request->query('tipo_reporte', 'individual');
        $empleadoId = $request->query('empleado_id');
        $fechaInicio = Carbon::parse($request->query('fecha_inicio'))->startOfDay();
        $fechaFin = Carbon::parse($request->query('fecha_fin'))->endOfDay();
        
        $incluirResumen = $request->boolean('incluir_resumen');
        $incluirDetalle = $request->boolean('incluir_detalle');
        $incluirObservaciones = $request->boolean('incluir_observaciones');
        $incluirMetodo = $request->boolean('incluir_metodo');

        if ($tipoReporte === 'individual') {
            // Reporte individual de un trabajador
            if (!$empleadoId) {
                abort(400, 'Se requiere empleado_id para reporte individual');
            }

            $empleado = Empleado::findOrFail($empleadoId);
            
            // Obtener asistencias del período
            $asistencias = Asistencia::where('empleado_id', $empleadoId)
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->orderBy('fecha', 'asc')
                ->get();

            // Calcular estadísticas
            $totalDias = $fechaInicio->diffInDays($fechaFin) + 1;
            $diasTrabajados = $asistencias->where('estado', 'presente')->count();
            $ausencias = $totalDias - $diasTrabajados;
            $porcentajeAsistencia = $totalDias > 0 ? ($diasTrabajados / $totalDias) * 100 : 0;
            
            // Calcular total de horas trabajadas
            $totalMinutos = 0;
            foreach ($asistencias as $asistencia) {
                if ($asistencia->hora_entrada && $asistencia->hora_salida) {
                    $entrada = Carbon::parse($asistencia->hora_entrada);
                    $salida = Carbon::parse($asistencia->hora_salida);
                    $totalMinutos += $entrada->diffInMinutes($salida);
                }
            }
            
            $totalHoras = floor($totalMinutos / 60);
            $totalMinutosRestantes = $totalMinutos % 60;
            $promedioHorasDia = $diasTrabajados > 0 ? $totalMinutos / $diasTrabajados : 0;
            $promedioHoras = floor($promedioHorasDia / 60);
            $promedioMinutos = round($promedioHorasDia % 60);

            // Obtener registros con observaciones o manuales
            $registrosEspeciales = $asistencias->filter(function ($asistencia) {
                return $asistencia->observacion || $asistencia->metodo_registro === 'manual_dni';
            });

            $data = [
                'tipo_reporte' => 'individual',
                'empleado' => $empleado,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'asistencias' => $asistencias,
                'estadisticas' => [
                    'total_dias' => $totalDias,
                    'dias_trabajados' => $diasTrabajados,
                    'ausencias' => $ausencias,
                    'porcentaje_asistencia' => $porcentajeAsistencia,
                    'total_horas' => $totalHoras,
                    'total_minutos' => $totalMinutosRestantes,
                    'promedio_horas' => $promedioHoras,
                    'promedio_minutos' => $promedioMinutos,
                ],
                'registros_especiales' => $registrosEspeciales,
                'incluir_resumen' => $incluirResumen,
                'incluir_detalle' => $incluirDetalle,
                'incluir_observaciones' => $incluirObservaciones,
                'incluir_metodo' => $incluirMetodo,
            ];

            $pdf = Pdf::loadView('reportes.asistencia-individual', $data);
            $filename = sprintf(
                'asistencia_%s_%s_%s.pdf',
                str_replace(' ', '_', $empleado->nombre_completo),
                $fechaInicio->format('Ymd'),
                $fechaFin->format('Ymd')
            );

            return $pdf->download($filename);
        } else {
            // Reporte general de todos los trabajadores
            $empleados = Empleado::where('estado_empleado', 'activo')
                ->orderBy('nombres')
                ->get();

            $reporteGeneral = [];
            $totalDias = $fechaInicio->diffInDays($fechaFin) + 1;

            foreach ($empleados as $empleado) {
                $asistencias = Asistencia::where('empleado_id', $empleado->id)
                    ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                    ->get();

                $diasTrabajados = $asistencias->where('estado', 'presente')->count();
                $ausencias = $totalDias - $diasTrabajados;
                $porcentajeAsistencia = $totalDias > 0 ? ($diasTrabajados / $totalDias) * 100 : 0;

                // Calcular horas trabajadas
                $totalMinutos = 0;
                foreach ($asistencias as $asistencia) {
                    if ($asistencia->hora_entrada && $asistencia->hora_salida) {
                        $entrada = Carbon::parse($asistencia->hora_entrada);
                        $salida = Carbon::parse($asistencia->hora_salida);
                        $totalMinutos += $entrada->diffInMinutes($salida);
                    }
                }

                $totalHoras = floor($totalMinutos / 60);
                $totalMinutosRestantes = $totalMinutos % 60;

                $reporteGeneral[] = [
                    'empleado' => $empleado,
                    'total_dias' => $totalDias,
                    'dias_trabajados' => $diasTrabajados,
                    'ausencias' => $ausencias,
                    'porcentaje_asistencia' => $porcentajeAsistencia,
                    'total_horas' => $totalHoras,
                    'total_minutos' => $totalMinutosRestantes,
                ];
            }

            // Calcular resumen general
            $totalTrabajadores = count($reporteGeneral);
            $promedioAsistenciaGeneral = $totalTrabajadores > 0 
                ? collect($reporteGeneral)->avg('porcentaje_asistencia') 
                : 0;
            $totalHorasGenerales = collect($reporteGeneral)->sum(function ($item) {
                return ($item['total_horas'] * 60) + $item['total_minutos'];
            });
            $horasGenerales = floor($totalHorasGenerales / 60);
            $minutosGenerales = $totalHorasGenerales % 60;

            $data = [
                'tipo_reporte' => 'general',
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'reporte_general' => $reporteGeneral,
                'resumen_general' => [
                    'total_trabajadores' => $totalTrabajadores,
                    'promedio_asistencia' => $promedioAsistenciaGeneral,
                    'total_horas' => $horasGenerales,
                    'total_minutos' => $minutosGenerales,
                ],
                'incluir_resumen' => $incluirResumen,
            ];

            $pdf = Pdf::loadView('reportes.asistencia-general', $data);
            $filename = sprintf(
                'asistencia_general_%s_%s.pdf',
                $fechaInicio->format('Ymd'),
                $fechaFin->format('Ymd')
            );

            return $pdf->download($filename);
        }
    }
}
