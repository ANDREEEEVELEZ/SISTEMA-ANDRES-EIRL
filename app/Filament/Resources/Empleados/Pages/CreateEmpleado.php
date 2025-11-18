<?php

namespace App\Filament\Resources\Empleados\Pages;

use App\Filament\Resources\Empleados\EmpleadoResource;
use App\Models\User;
use App\Services\FaceRecognitionService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateEmpleado extends CreateRecord
{
    protected static string $resource = EmpleadoResource::class;

    /**
     * Validar datos antes de crear
     */
    protected function getFormRules(): array
    {
        $rules = parent::getFormRules();
        
        // Agregar validación personalizada para correo
        $rules['correo_empleado'][] = function (string $attribute, $value, \Closure $fail) {
            if (\App\Models\User::where('email', $value)->exists()) {
                $fail('Este correo electrónico ya está registrado en el sistema.');
            }
        };
        
        return $rules;
    }

    /**
     * Mutate los datos antes de crear el registro
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Convertir fecha de nacimiento del formato dd/mm/yyyy a yyyy-mm-dd
        if (!empty($data['fecha_nacimiento'])) {
            try {
                $fechaNacimiento = \Carbon\Carbon::createFromFormat('d/m/Y', $data['fecha_nacimiento']);
                $data['fecha_nacimiento'] = $fechaNacimiento->format('Y-m-d');
            } catch (\Exception $e) {
                // Si no se puede convertir, mantener el valor original
                \Log::warning('Error al convertir fecha de nacimiento: ' . $e->getMessage());
            }
        }

        // Convertir fecha de incorporación del formato d/m/Y a Y-m-d si está en formato de fecha
        if (!empty($data['fecha_incorporacion']) && is_string($data['fecha_incorporacion'])) {
            try {
                if (strpos($data['fecha_incorporacion'], '/') !== false) {
                    $fechaIncorporacion = \Carbon\Carbon::createFromFormat('d/m/Y', $data['fecha_incorporacion']);
                    $data['fecha_incorporacion'] = $fechaIncorporacion->format('Y-m-d');
                }
            } catch (\Exception $e) {
                // Si no se puede convertir, mantener el valor original
                \Log::warning('Error al convertir fecha de incorporación: ' . $e->getMessage());
            }
        }

        // Crear automáticamente un usuario para el empleado
        $user = User::create([
            'name' => $data['nombres'] . ' ' . $data['apellidos'],
            'email' => $data['correo_empleado'] ?? strtolower(Str::slug($data['nombres'] . '-' . $data['apellidos'])) . '@empresa.com',
            'password' => Hash::make($data['dni']), // Contraseña inicial es el DNI
        ]);

        // Asignar el rol seleccionado en el formulario
        if (!empty($data['rol'])) {
            $user->assignRole($data['rol']);
        } else {
            // Fallback al rol de vendedor si no se especifica
            $user->assignRole('vendedor');
        }

        // Asignar el user_id al empleado
        $data['user_id'] = $user->id;
        
        // Procesar imagen facial si existe
        if (!empty($data['captured_face_image']) && !empty($data['face_descriptors'])) {
            // Los datos faciales se procesarán en afterCreate
            // Pero aseguramos que los campos estén presentes
            \Log::info('Datos faciales detectados en creación', [
                'tiene_imagen' => !empty($data['captured_face_image']),
                'tiene_descriptores' => !empty($data['face_descriptors']),
                'dni' => $data['dni']
            ]);
        }

        return $data;
    }

    /**
     * Después de crear el empleado, procesar la imagen facial si existe
     */
    protected function afterCreate(): void
    {
        // Obtener todos los datos del formulario incluyendo campos ocultos
        $formData = $this->data;
        
        \Log::info('Datos en afterCreate', [
            'captured_face_image_exists' => isset($formData['captured_face_image']),
            'face_descriptors_exists' => isset($formData['face_descriptors']),
            'empleado_id' => $this->record->id,
            'dni' => $this->record->dni
        ]);
        
        // Verificar si hay imagen capturada
        if (!empty($formData['captured_face_image']) && !empty($formData['face_descriptors'])) {
            $faceService = app(FaceRecognitionService::class);
            
            try {
                $descriptors = is_string($formData['face_descriptors']) 
                    ? json_decode($formData['face_descriptors'], true) 
                    : $formData['face_descriptors'];
                
                $success = $faceService->registerFaceDescriptors(
                    $this->record->id,
                    $descriptors,
                    $this->record->dni,
                    $formData['captured_face_image']
                );
                
                if ($success) {
                    Notification::make()
                        ->title('✅ Rostro registrado correctamente')
                        ->body('El empleado puede usar reconocimiento facial para marcar asistencia.')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('⚠️ Error al guardar el rostro')
                        ->body('El empleado fue creado pero hubo un problema al guardar la imagen facial.')
                        ->warning()
                        ->send();
                }
            } catch (\Exception $e) {
                \Log::error('Error procesando imagen facial: ' . $e->getMessage());
                Notification::make()
                    ->title('⚠️ Error al procesar el rostro')
                    ->body('Error: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }
        } else {
            \Log::warning('No se encontraron datos faciales para procesar');
        }
    }
}

