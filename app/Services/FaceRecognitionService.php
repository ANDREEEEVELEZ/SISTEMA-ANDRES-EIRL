<?php

namespace App\Services;

use App\Models\Empleado;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FaceRecognitionService
{
    /**
     * Umbral de similitud para considerar una coincidencia facial
     */
    private const SIMILARITY_THRESHOLD = 0.6;

    /**
     * Registra los descriptores faciales de un empleado
     * 
     * @param int $empleadoId
     * @param array $faceDescriptors Descriptores faciales obtenidos de Face-API.js
     * @param string $fotoPath Ruta de la foto de referencia
     * @return bool
     */
    public function registerFaceDescriptors(int $empleadoId, array $faceDescriptors, string $fotoPath): bool
    {
        try {
            $empleado = Empleado::findOrFail($empleadoId);
            
            // Guardar descriptores como JSON
            $empleado->update([
                'face_descriptors' => json_encode($faceDescriptors),
                'foto_facial_path' => $fotoPath
            ]);

            Log::info("Descriptores faciales registrados para empleado: {$empleado->nombres}");
            
            return true;
        } catch (\Exception $e) {
            Log::error("Error registrando descriptores faciales: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Identifica al empleado comparando descriptores faciales
     * 
     * @param array $capturedDescriptors Descriptores de la foto capturada en tiempo real
     * @return Empleado|null
     */
    public function identifyEmployee(array $capturedDescriptors): ?Empleado
    {
        try {
            // Obtener todos los empleados con descriptores faciales registrados
            $empleados = Empleado::whereNotNull('face_descriptors')->get();

            $bestMatch = null;
            $bestSimilarity = 0;

            foreach ($empleados as $empleado) {
                $storedDescriptors = json_decode($empleado->face_descriptors, true);
                
                if (!$storedDescriptors) {
                    continue;
                }

                // Calcular similitud entre descriptores
                $similarity = $this->calculateSimilarity($capturedDescriptors, $storedDescriptors);

                Log::info("Similitud con {$empleado->nombres}: {$similarity}");

                // Si la similitud es mayor al umbral y es la mejor hasta ahora
                if ($similarity >= self::SIMILARITY_THRESHOLD && $similarity > $bestSimilarity) {
                    $bestSimilarity = $similarity;
                    $bestMatch = $empleado;
                }
            }

            if ($bestMatch) {
                Log::info("Empleado identificado: {$bestMatch->nombres} (similitud: {$bestSimilarity})");
                return $bestMatch;
            }

            Log::warning("No se pudo identificar al empleado. Mejor similitud: {$bestSimilarity}");
            return null;

        } catch (\Exception $e) {
            Log::error("Error identificando empleado: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Calcula la similitud entre dos conjuntos de descriptores faciales
     * Utiliza distancia euclidiana normalizada
     * 
     * @param array $descriptors1
     * @param array $descriptors2
     * @return float Valor entre 0 y 1 (1 = idéntico)
     */
    private function calculateSimilarity(array $descriptors1, array $descriptors2): float
    {
        if (count($descriptors1) !== count($descriptors2)) {
            return 0;
        }

        $sumSquaredDiffs = 0;
        $count = count($descriptors1);

        for ($i = 0; $i < $count; $i++) {
            $diff = $descriptors1[$i] - $descriptors2[$i];
            $sumSquaredDiffs += $diff * $diff;
        }

        // Distancia euclidiana
        $distance = sqrt($sumSquaredDiffs);
        
        // Normalizar a un valor entre 0 y 1 (1 = más similar)
        // Usamos una función exponencial decreciente
        $similarity = exp(-$distance);

        return $similarity;
    }

    /**
     * Verifica si un empleado tiene descriptores faciales registrados
     * 
     * @param int $empleadoId
     * @return bool
     */
    public function hasRegisteredFace(int $empleadoId): bool
    {
        $empleado = Empleado::find($empleadoId);
        return $empleado && !empty($empleado->face_descriptors);
    }

    /**
     * Elimina los descriptores faciales de un empleado
     * 
     * @param int $empleadoId
     * @return bool
     */
    public function deleteFaceData(int $empleadoId): bool
    {
        try {
            $empleado = Empleado::findOrFail($empleadoId);
            
            // Eliminar foto de referencia si existe
            if ($empleado->foto_facial_path && Storage::exists($empleado->foto_facial_path)) {
                Storage::delete($empleado->foto_facial_path);
            }

            // Limpiar datos faciales
            $empleado->update([
                'face_descriptors' => null,
                'foto_facial_path' => null
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Error eliminando datos faciales: " . $e->getMessage());
            return false;
        }
    }
}