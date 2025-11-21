<?php

namespace App\Filament\Resources\Empleados\Pages;

use App\Filament\Resources\Empleados\EmpleadoResource;
use App\Services\FaceRecognitionService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditEmpleado extends EditRecord
{
    protected static string $resource = EmpleadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Los empleados no se eliminan, solo se inactivan
        ];
    }

    /**
     * Cargar datos antes de llenar el formulario (incluyendo rol)
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Cargar el rol actual del usuario
        if ($this->record->user && $this->record->user->roles->isNotEmpty()) {
            $data['rol'] = $this->record->user->roles->first()->name;
        }

        // Convertir fecha de nacimiento de Y-m-d a d/m/Y para mostrar en el formulario
        if (!empty($data['fecha_nacimiento'])) {
            try {
                $fechaNacimiento = \Carbon\Carbon::parse($data['fecha_nacimiento']);
                $data['fecha_nacimiento'] = $fechaNacimiento->format('d/m/Y');
            } catch (\Exception $e) {
                // Si no se puede convertir, mantener el valor original
            }
        }
        
        return $data;
    }

    /**
     * Validar datos antes de guardar
     */
    protected function getFormRules(): array
    {
        $rules = parent::getFormRules();
        
        // Agregar validación personalizada para correo
        $rules['correo_empleado'][] = function (string $attribute, $value, \Closure $fail) {
            $query = \App\Models\User::where('email', $value);
            
            if ($this->record && $this->record->user_id) {
                $query->where('id', '!=', $this->record->user_id);
            }
            
            if ($query->exists()) {
                $fail('Este correo electrónico ya está registrado en el sistema.');
            }
        };
        
        return $rules;
    }

    /**
     * Sincronizar datos antes de guardar
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Convertir fecha de nacimiento del formato dd/mm/yyyy a yyyy-mm-dd
        if (!empty($data['fecha_nacimiento'])) {
            try {
                $fechaNacimiento = \Carbon\Carbon::createFromFormat('d/m/Y', $data['fecha_nacimiento']);
                $data['fecha_nacimiento'] = $fechaNacimiento->format('Y-m-d');
            } catch (\Exception $e) {
                // Si no se puede convertir, mantener el valor original
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
            }
        }

        // Sincronizar el correo, nombre y rol en la tabla users
        if ($this->record->user) {
            $emailCambiado = $this->record->user->email !== $data['correo_empleado'];
            $rolCambiado = false;
            
            $this->record->user->update([
                'email' => $data['correo_empleado'] ?? $this->record->user->email,
                'name' => $data['nombres'] . ' ' . $data['apellidos'],
            ]);
            
            // Actualizar rol si cambió
            if (!empty($data['rol'])) {
                $rolActual = $this->record->user->roles->first()?->name;
                if ($rolActual !== $data['rol']) {
                    // Remover roles anteriores y asignar el nuevo
                    $this->record->user->syncRoles([$data['rol']]);
                    $rolCambiado = true;
                }
            }
            
            // Notificaciones
            if ($emailCambiado) {
                Notification::make()
                    ->title('Correo actualizado')
                    ->body('El correo de acceso al sistema ha sido actualizado correctamente.')
                    ->success()
                    ->send();
            }
            
            if ($rolCambiado) {
                Notification::make()
                    ->title('Rol actualizado')
                    ->body("El rol del empleado ha sido cambiado a: {$data['rol']}")
                    ->success()
                    ->send();
            }
        }
        
        return $data;
    }

    /**
     * Después de actualizar el empleado, procesar cambios en la imagen facial
     */
    protected function afterSave(): void
    {
        $data = $this->form->getRawState();
        $faceService = app(FaceRecognitionService::class);
        
        // Si se capturó una nueva imagen
        if (!empty($data['captured_face_image']) && !empty($data['face_descriptors'])) {
            $success = $faceService->registerFaceDescriptors(
                $this->record->id,
                json_decode($data['face_descriptors'], true),
                $this->record->dni,
                $data['captured_face_image']
            );
            
            if ($success) {
                Notification::make()
                    ->title('✅ Rostro actualizado correctamente')
                    ->body('Los datos faciales han sido actualizados en el sistema.')
                    ->success()
                    ->send();
            }
        }
        
        // Si se marcó para eliminar (campos vacíos después de tener datos)
        if (empty($data['face_descriptors']) && $this->record->getOriginal('face_descriptors')) {
            $success = $faceService->deleteFaceData($this->record->id);
            
            if ($success) {
                Notification::make()
                    ->title('🗑️ Rostro eliminado')
                    ->body('Los datos faciales han sido eliminados del sistema.')
                    ->warning()
                    ->send();
            }
        }
    }
}
