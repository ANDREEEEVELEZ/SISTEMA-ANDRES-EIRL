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
     * NOTA: Este método se mantiene por compatibilidad pero ya no se usa
     * El registro ahora se hace desde el módulo de Filament
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
            
            // Obtener el empleado para conseguir su DNI
            $empleado = Empleado::findOrFail($empleadoId);

            // Registrar descriptores faciales con el nuevo método
            $success = $this->faceRecognitionService->registerFaceDescriptors(
                $empleadoId, 
                $faceDescriptors, 
                $empleado->dni,
                $photoBase64
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
                    'message' => 'No se pudo identificar al empleado. Asegúrate de que tu rostro esté registrado.',
                    'no_match' => true // Flag para que el frontend cuente el intento fallido
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
                        'empleado' => $empleado->nombres . ' ' . $empleado->apellidos,
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
                    'estado' => 'presente',
                    'metodo_registro' => 'facial'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Entrada registrada correctamente para {$empleado->nombres}",
                    'empleado' => $empleado->nombres . ' ' . $empleado->apellidos,
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
     * Marca asistencia manualmente usando DNI (sistema de respaldo)
     */
    public function markAttendanceManual(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dni' => 'required|string|max:15',
            'razon_manual' => 'required|string|min:10|max:500',
            'intentos_fallidos' => 'required|integer|min:3'
        ], [
            'dni.required' => 'El DNI es obligatorio',
            'razon_manual.required' => 'Debe explicar el motivo del registro manual',
            'razon_manual.min' => 'La explicación debe tener al menos 10 caracteres',
            'intentos_fallidos.min' => 'Este método solo está disponible después de 3 intentos fallidos'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $dni = $request->dni;
            $razonManual = $request->razon_manual;
            $intentosFallidos = $request->intentos_fallidos;

            // Validar que el DNI exista en el sistema
            $empleado = $this->faceRecognitionService->validateDNIForManualAttendance($dni);

            if (!$empleado) {
                return response()->json([
                    'success' => false,
                    'message' => 'DNI no encontrado en el sistema. Verifica que esté correcto.'
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
                        'message' => "Salida registrada manualmente para {$empleado->nombres}",
                        'empleado' => $empleado->nombres . ' ' . $empleado->apellidos,
                        'tipo' => 'salida',
                        'hora' => Carbon::now()->format('H:i:s'),
                        'metodo' => 'manual'
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => "Ya has registrado entrada y salida hoy, {$empleado->nombres}"
                    ]);
                }
            } else {
                // Marcar entrada manual
                Asistencia::create([
                    'empleado_id' => $empleado->id,
                    'fecha' => $hoy,
                    'hora_entrada' => Carbon::now()->format('H:i:s'),
                    'estado' => 'presente',
                    'metodo_registro' => 'manual_dni',
                    'razon_manual' => $razonManual,
                    'intentos_fallidos' => $intentosFallidos
                ]);

                Log::warning("Asistencia registrada manualmente para {$empleado->nombres} (DNI: {$dni}). Motivo: {$razonManual}");

                return response()->json([
                    'success' => true,
                    'message' => "Entrada registrada manualmente para {$empleado->nombres}",
                    'empleado' => $empleado->nombres . ' ' . $empleado->apellidos,
                    'tipo' => 'entrada',
                    'hora' => Carbon::now()->format('H:i:s'),
                    'metodo' => 'manual',
                    'advertencia' => 'Se recomienda actualizar tu registro facial en el sistema.'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error en registro manual de asistencia: ' . $e->getMessage());
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
