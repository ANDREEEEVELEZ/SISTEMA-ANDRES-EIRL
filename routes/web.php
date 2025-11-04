<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AsistenciaFotoController;
use App\Http\Controllers\FaceRecognitionController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\ClienteExportController;
use App\Http\Controllers\ReporteInventarioController;
use App\Http\Controllers\VentaExportController;

Route::get('/', function () {
    return view('welcome');
});

// Rutas para manejo de fotos de asistencia
Route::post('/asistencia/guardar-foto', [AsistenciaFotoController::class, 'guardarFoto'])->name('asistencia.guardar-foto');
Route::get('/asistencia/foto/{ruta}', [AsistenciaFotoController::class, 'obtenerFoto'])->name('asistencia.obtener-foto');

// Rutas para reconocimiento facial
Route::prefix('face-recognition')->group(function () {
    // Páginas
    Route::get('/attendance', [FaceRecognitionController::class, 'attendancePage'])->name('face.attendance');
    Route::get('/register', [FaceRecognitionController::class, 'registrationPage'])->name('face.register'); // Deprecada, mantener por compatibilidad

    // API endpoints
    Route::post('/register-face', [FaceRecognitionController::class, 'registerFace'])->name('face.register.store'); // Deprecada
    Route::post('/mark-attendance', [FaceRecognitionController::class, 'markAttendanceByFace'])->name('face.attendance.mark');
    Route::post('/mark-attendance-manual', [FaceRecognitionController::class, 'markAttendanceManual'])->name('face.attendance.manual'); // NUEVO
    Route::get('/employees', [FaceRecognitionController::class, 'getEmployeesForRegistration'])->name('face.employees'); // Deprecada


    Route::get('/consultar-documento', [ClientesController::class, 'buscarPorDocumento']);

});

// Rutas para impresión de comprobantes
Route::get('/comprobantes/{id}/imprimir', [ComprobanteController::class, 'imprimirComprobante'])
    ->name('comprobante.imprimir');
Route::get('/comprobantes/{id}/ticket', [ComprobanteController::class, 'imprimirTicket'])
    ->name('comprobante.ticket');

// Exportar clientes a PDF
Route::get('/clientes/exportar/pdf', [ClienteExportController::class, 'exportarPdf'])
    ->name('clientes.exportar.pdf');
Route::get('/clientes/{id}/imprimir/pdf', [ClienteExportController::class, 'imprimirClientePdf'])
    ->name('clientes.imprimir.pdf');

// Exportar ventas a CSV
Route::get('/ventas/exportar', [VentaExportController::class, 'export'])
    ->name('ventas.export');

// Reportes - Arqueo de caja (descarga PDF de arqueo registrado)
Route::get('/reportes/arqueo/{id}/pdf', [\App\Http\Controllers\ReportesController::class, 'arqueoPdf'])
    ->name('reportes.arqueo');

// Reportes - Exportación de cajas por filtros (stream PDF)
Route::get('/reportes/cajas/export', [\App\Http\Controllers\ReportesController::class, 'cajasExport'])
    ->name('reportes.cajas_export');

// Ruta intermedia para abrir el PDF en nueva pestaña y regresar al sistema
Route::get('/reportes/cajas/open', [\App\Http\Controllers\ReportesController::class, 'cajasOpen'])
    ->name('reportes.cajas_open');

// Reportes - Asistencias (PDF)
Route::get('/reportes/asistencias/pdf', [\App\Http\Controllers\ReportesController::class, 'asistenciasPdf'])
    ->name('reportes.asistencias.pdf')
    ->middleware(['auth']);

// Reportes de Inventario
Route::prefix('reportes/inventario')->middleware(['auth'])->group(function () {
    Route::get('/stock', [ReporteInventarioController::class, 'reporteStock'])
        ->name('reportes.inventario.stock');
    Route::get('/movimientos', [ReporteInventarioController::class, 'reporteMovimientos'])
        ->name('reportes.inventario.movimientos');
    Route::get('/completo', [ReporteInventarioController::class, 'reporteCompleto'])
        ->name('reportes.inventario.completo');
});
