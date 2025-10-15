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
     * @param string $dni DNI del empleado para nombrar el archivo
     * @param string $photoBase64 Imagen en base64
     * @return bool
     */
    public function registerFaceDescriptors(int $empleadoId, array $faceDescriptors, string $dni, string $photoBase64): bool
    {
        try {
            $empleado = Empleado::findOrFail($empleadoId);
            
            // Eliminar foto anterior si existe
            if ($empleado->foto_facial_path && Storage::disk('public')->exists($empleado->foto_facial_path)) {
                Storage::disk('public')->delete($empleado->foto_facial_path);
                Log::info("Foto anterior eliminada: {$empleado->foto_facial_path}");
            }
            
            // Guardar nueva foto con formato: Empleado_{DNI}.jpg
            $photoPath = $this->saveFaceImage($dni, $photoBase64);
            
            // Guardar descriptores como JSON
            $empleado->update([
                'face_descriptors' => json_encode($faceDescriptors),
                'foto_facial_path' => $photoPath
            ]);

            Log::info("Descriptores faciales registrados para empleado: {$empleado->nombres} (DNI: {$dni})");
            
            return true;
        } catch (\Exception $e) {
            Log::error("Error registrando descriptores faciales: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Guarda la imagen facial usando el DNI como nombre
     * 
     * @param string $dni
     * @param string $photoBase64
     * @return string Ruta relativa del archivo guardado
     */
    private function saveFaceImage(string $dni, string $photoBase64): string
    {
        // Decodificar base64
        $photoData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $photoBase64));
        
        // Generar nombre de archivo: Empleado_12345678.jpg
        $filename = "Empleado_{$dni}.jpg";
        $path = "empleados_rostros/{$filename}";
        
        // Guardar en storage/app/public/empleados_rostros/
        Storage::disk('public')->put($path, $photoData);
        
        Log::info("Imagen facial guardada: {$path}");
        
        return $path;
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
            if ($empleado->foto_facial_path && Storage::disk('public')->exists($empleado->foto_facial_path)) {
                Storage::disk('public')->delete($empleado->foto_facial_path);
                Log::info("Foto facial eliminada: {$empleado->foto_facial_path}");
            }

            // Limpiar datos faciales
            $empleado->update([
                'face_descriptors' => null,
                'foto_facial_path' => null
            ]);

            Log::info("Datos faciales eliminados para empleado: {$empleado->nombres}");
            
            return true;
        } catch (\Exception $e) {
            Log::error("Error eliminando datos faciales: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Valida si un DNI existe en la base de datos
     * 
     * @param string $dni
     * @return Empleado|null
     */
    public function validateDNIForManualAttendance(string $dni): ?Empleado
    {
        try {
            return Empleado::where('dni', $dni)->first();
        } catch (\Exception $e) {
            Log::error("Error validando DNI: " . $e->getMessage());
            return null;
        }
    }
}