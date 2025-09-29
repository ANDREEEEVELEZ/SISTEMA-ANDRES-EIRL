<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AsistenciaFotoController extends Controller
{
    public function guardarFoto(Request $request)
    {
        try {
            // Validar que se reciba la imagen
            $request->validate([
                'foto' => 'required|string',
                'empleado_id' => 'required|integer'
            ]);

            // Obtener datos de la imagen en base64
            $imagenBase64 = $request->foto;
            $empleadoId = $request->empleado_id;
            
            // Remover el prefijo de data URL si existe
            if (strpos($imagenBase64, ',') !== false) {
                $imagenBase64 = explode(',', $imagenBase64)[1];
            }
            
            // Decodificar la imagen
            $imagenDecodificada = base64_decode($imagenBase64);
            
            // Crear nombre único para el archivo
            $fecha = Carbon::now();
            $nombreArchivo = sprintf(
                'empleado_%d_%s.jpg',
                $empleadoId,
                $fecha->format('Ymd_His')
            );
            
            // Crear la estructura de directorios por año y mes
            $rutaDirectorio = sprintf(
                'asistencias/fotos/%d/%02d',
                $fecha->year,
                $fecha->month
            );
            
            // Ruta completa del archivo
            $rutaCompleta = $rutaDirectorio . '/' . $nombreArchivo;
            
            // Crear directorios si no existen
            Storage::disk('public')->makeDirectory($rutaDirectorio);
            
            // Guardar la imagen
            $guardado = Storage::disk('public')->put($rutaCompleta, $imagenDecodificada);
            
            if ($guardado) {
                return response()->json([
                    'success' => true,
                    'mensaje' => 'Foto guardada correctamente',
                    'ruta' => $rutaCompleta,
                    'nombre_archivo' => $nombreArchivo,
                    'url' => asset('storage/' . $rutaCompleta)
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'Error al guardar la foto'
                ], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function obtenerFoto($ruta)
    {
        try {
            // Verificar que el archivo existe
            if (!Storage::disk('public')->exists($ruta)) {
                abort(404, 'Foto no encontrada');
            }
            
            // Obtener el archivo
            $archivo = Storage::disk('public')->get($ruta);
            $extension = pathinfo($ruta, PATHINFO_EXTENSION);
            $tipoMime = $extension === 'jpg' || $extension === 'jpeg' ? 'image/jpeg' : 'image/png';
            
            return response($archivo)->header('Content-Type', $tipoMime);
            
        } catch (\Exception $e) {
            abort(404, 'Error al cargar la foto');
        }
    }
}
