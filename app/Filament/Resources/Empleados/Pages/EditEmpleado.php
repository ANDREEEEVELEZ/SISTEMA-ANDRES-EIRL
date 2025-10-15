<?php

namespace App\Filament\Resources\Empleados\Pages;

use App\Filament\Resources\Empleados\EmpleadoResource;
use App\Services\FaceRecognitionService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditEmpleado extends EditRecord
{
    protected static string $resource = EmpleadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
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
