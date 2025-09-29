<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AsistenciaFotoController;

Route::get('/', function () {
    return view('welcome');
});

// Rutas para manejo de fotos de asistencia
Route::post('/asistencia/guardar-foto', [AsistenciaFotoController::class, 'guardarFoto'])->name('asistencia.guardar-foto');
Route::get('/asistencia/foto/{ruta}', [AsistenciaFotoController::class, 'obtenerFoto'])->name('asistencia.obtener-foto');
