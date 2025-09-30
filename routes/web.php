<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AsistenciaFotoController;
use App\Http\Controllers\FaceRecognitionController;

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
    Route::get('/register', [FaceRecognitionController::class, 'registrationPage'])->name('face.register');
    
    // API endpoints
    Route::post('/register-face', [FaceRecognitionController::class, 'registerFace'])->name('face.register.store');
    Route::post('/mark-attendance', [FaceRecognitionController::class, 'markAttendanceByFace'])->name('face.attendance.mark');
    Route::get('/employees', [FaceRecognitionController::class, 'getEmployeesForRegistration'])->name('face.employees');
});
