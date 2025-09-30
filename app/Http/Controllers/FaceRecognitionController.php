<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Empleado;
use App\Services\FaceRecognitionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FaceRecognitionController extends Controller
{
    protected $faceRecognitionService;

    public function __construct(FaceRecognitionService $faceRecognitionService)
    {
        $this->faceRecognitionService = $faceRecognitionService;
    }

    /**
     * Registra la foto facial de referencia de un empleado
     */
    public function registerFace(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'empleado_id' => 'required|exists:empleados,id',
            'face_descriptors' => 'required|array',
            'face_descriptors.*' => 'required|numeric',
            'photo' => 'required|string' // Base64 de la foto
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $empleadoId = $request->empleado_id;
            $faceDescriptors = $request->face_descriptors;
            $photoBase64 = $request->photo;

            // Decodificar y guardar la foto de referencia
            $photoData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $photoBase64));
            $photoPath = "facial_references/{$empleadoId}_" . time() . ".jpg";
            
            Storage::put($photoPath, $photoData);

            // Registrar descriptores faciales
            $success = $this->faceRecognitionService->registerFaceDescriptors(
                $empleadoId, 
                $faceDescriptors, 
                $photoPath
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Foto facial registrada correctamente'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al registrar la foto facial'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error registrando foto facial: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Marca asistencia usando reconocimiento facial
     */
    public function markAttendanceByFace(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'face_descriptors' => 'required|array',
            'face_descriptors.*' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Descriptores faciales inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $faceDescriptors = $request->face_descriptors;

            // Identificar al empleado
            $empleado = $this->faceRecognitionService->identifyEmployee($faceDescriptors);

            if (!$empleado) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo identificar al empleado. Asegúrate de que tu rostro esté registrado.'
                ], 404);
            }

            // Verificar si ya marcó asistencia hoy
            $hoy = Carbon::today();
            $asistenciaExistente = Asistencia::where('empleado_id', $empleado->id)
                ->whereDate('fecha', $hoy)
                ->first();

            if ($asistenciaExistente) {
                // Si ya marcó entrada, marcar salida
                if (!$asistenciaExistente->hora_salida) {
                    $asistenciaExistente->update([
                        'hora_salida' => Carbon::now()->format('H:i:s')
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => "Salida registrada correctamente para {$empleado->nombres}",
                        'empleado' => $empleado->nombres,
                        'tipo' => 'salida',
                        'hora' => Carbon::now()->format('H:i:s')
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => "Ya has registrado entrada y salida hoy, {$empleado->nombres}"
                    ]);
                }
            } else {
                // Marcar entrada
                Asistencia::create([
                    'empleado_id' => $empleado->id,
                    'fecha' => $hoy,
                    'hora_entrada' => Carbon::now()->format('H:i:s'),
                    'estado' => 'presente'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Entrada registrada correctamente para {$empleado->nombres}",
                    'empleado' => $empleado->nombres,
                    'tipo' => 'entrada',
                    'hora' => Carbon::now()->format('H:i:s')
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error marcando asistencia facial: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtiene la lista de empleados para registro facial
     */
    public function getEmployeesForRegistration()
    {
        try {
            $empleados = Empleado::select('id', 'nombres', 'apellidos', 'face_descriptors')
                ->get()
                ->map(function ($empleado) {
                    return [
                        'id' => $empleado->id,
                        'nombre_completo' => $empleado->nombres . ' ' . $empleado->apellidos,
                        'tiene_rostro_registrado' => !empty($empleado->face_descriptors)
                    ];
                });

            return response()->json([
                'success' => true,
                'empleados' => $empleados
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo empleados: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Página principal para marcar asistencia
     */
    public function attendancePage()
    {
        return view('face-attendance.index');
    }

    /**
     * Página para registrar rostros de empleados
     */
    public function registrationPage()
    {
        return view('face-attendance.register');
    }
}
