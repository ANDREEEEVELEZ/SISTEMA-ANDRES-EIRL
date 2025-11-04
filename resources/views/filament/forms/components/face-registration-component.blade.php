@php
    $record = $getRecord();
    $isEditing = $record !== null;
    $hasPhoto = $record?->foto_facial_path !== null;
    $empleadoId = $record?->id ?? null;
@endphp

<div 
    x-data="faceRegistrationComponent(@js($empleadoId), @js($isEditing))"
    x-init="init()"
    class="space-y-4"
    @trigger-open-camera.window="openCamera()"
    @trigger-delete-photo.window="deletePhoto()"
>
    <!-- Botones de Acción - OCULTOS, ahora están en la tarjeta -->
    <div class="hidden">
        <button type="button" @click="openCamera()" x-show="!cameraOpen"></button>
        @if($hasPhoto && $isEditing)
            <button type="button" @click="deletePhoto()"></button>
        @endif
    </div>
    
    <!-- Modal de Cámara -->
    <div 
        x-show="cameraOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
        @keydown.escape.window="closeCamera()"
    >
        <div class="flex items-center justify-center min-h-screen px-4 py-6">
            <!-- Overlay -->
            <div 
                class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity"
                @click="closeCamera()"
            ></div>
            
            <!-- Modal Content -->
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-2xl transform transition-all max-w-3xl w-full">
                <!-- Header -->
                <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <x-filament::icon 
                                icon="heroicon-o-camera" 
                                class="w-5 h-5"
                            />
                            Captura de Rostro Facial
                        </h3>
                        <button
                            type="button"
                            @click="closeCamera()"
                            class="text-white hover:text-gray-200 transition-colors"
                        >
                            <x-filament::icon 
                                icon="heroicon-o-x-mark" 
                                class="w-6 h-6"
                            />
                        </button>
                    </div>
                </div>
                
                <div class="p-6 space-y-4">
                    <!-- Video de Cámara -->
                    <div class="relative bg-gray-900 rounded-xl overflow-hidden shadow-inner" x-show="!capturedImage">
                        <div class="relative" style="width: 100%; height: 500px; position: relative;">
                            <video 
                                x-ref="video"
                                autoplay 
                                muted
                                playsinline
                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;"
                            ></video>
                            
                            <!-- Canvas para detectiones en tiempo real -->
                            <canvas 
                                x-ref="canvas"
                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 10; pointer-events: none;"
                            ></canvas>
                        </div>
                        
                        <!-- Indicador de Estado -->
                        <div 
                            class="absolute top-4 right-4 px-4 py-2 rounded-full text-sm font-semibold shadow-lg transition-all backdrop-blur-md"
                            :class="status.color"
                        >
                            <span class="flex items-center gap-2">
                                <x-filament::loading-indicator 
                                    x-show="status.loading" 
                                    class="h-4 w-4"
                                />
                                <span x-text="status.text"></span>
                            </span>
                        </div>
                        
                        <!-- Contador de rostros detectados -->
                        <div 
                            x-show="faceDetectedCount > 0"
                            class="absolute top-4 left-4 px-4 py-2 rounded-full text-sm font-semibold shadow-lg transition-all backdrop-blur-md bg-green-500 text-white"
                        >
                            <span x-text="'Rostro detectado'"></span>
                        </div>
                        
                        <!-- Instrucciones -->
                        <div class="absolute bottom-4 left-4 right-4 bg-black/80 backdrop-blur-sm rounded-lg px-4 py-3 border border-cyan-400/30">
                            <p class="text-white text-sm text-center font-medium">
                                <span x-text="detectionMessage"></span>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Vista Previa Capturada -->
                    <div x-show="capturedImage" class="space-y-3">
                        <div class="bg-gradient-to-br from-success-50 to-success-100 dark:from-success-900/20 dark:to-success-800/20 rounded-xl p-6 border-2 border-success-300">
                            <h4 class="text-sm font-semibold text-success-800 dark:text-success-300 mb-4 flex items-center gap-2">
                                <x-filament::icon 
                                    icon="heroicon-o-check-circle" 
                                    class="w-5 h-5"
                                />
                                Vista Previa - Rostro Capturado
                            </h4>
                            <div class="flex justify-center">
                                <img 
                                    :src="capturedImage" 
                                    alt="Vista previa"
                                    class="w-56 h-56 rounded-full object-cover border-4 border-success-500 shadow-xl"
                                />
                            </div>
                        </div>
                        
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                            <p class="text-sm text-blue-800 dark:text-blue-300 text-center">
                                <strong>Importante:</strong> Verifica que la imagen sea clara y el rostro sea visible. 
                                Si no es así, puedes volver a tomarla.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Botones de Acción -->
                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="closeCamera()"
                            class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-colors"
                        >
                            Cancelar
                        </button>
                        
                        <button
                            type="button"
                            @click="capturePhoto()"
                            x-show="!capturedImage"
                            :disabled="!modelsLoaded"
                            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium shadow-sm hover:shadow transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <x-filament::icon 
                                icon="heroicon-o-camera" 
                                class="w-4 h-4"
                            />
                            Capturar Rostro
                        </button>
                        
                        <button
                            type="button"
                            @click="retakePhoto()"
                            x-show="capturedImage"
                            class="px-5 py-2.5 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-medium shadow-sm hover:shadow transition-all flex items-center gap-2"
                        >
                            <x-filament::icon 
                                icon="heroicon-o-arrow-path" 
                                class="w-4 h-4"
                            />
                            Volver a Tomar
                        </button>
                        
                        <button
                            type="button"
                            @click="confirmPhoto()"
                            x-show="capturedImage"
                            class="px-5 py-2.5 bg-success-600 hover:bg-success-700 text-white rounded-lg font-medium shadow-sm hover:shadow transition-all flex items-center gap-2"
                        >
                            <x-filament::icon 
                                icon="heroicon-o-check" 
                                class="w-4 h-4"
                            />
                            Confirmar y Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.13/dist/face-api.min.js"></script>
<script>
function faceRegistrationComponent(empleadoId, isEditing) {
    return {
        empleadoId: empleadoId,
        isEditing: isEditing,
        cameraOpen: false,
        modelsLoaded: false,
        capturedImage: null,
        faceDescriptors: null,
        stream: null,
        detectionInterval: null,
        faceDetectedCount: 0,
        detectionMessage: 'Posiciona tu rostro frente a la cámara',
        status: {
            text: 'Cargando modelos...',
            color: 'bg-yellow-500 text-white',
            loading: true
        },
        
        async init() {
            await this.loadModels();
        },
        
        async loadModels() {
            try {
                console.log('Cargando modelos de IA...');
                const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.13/model';
                
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                ]);
                
                this.modelsLoaded = true;
                console.log('Modelos cargados exitosamente');
            } catch (error) {
                console.error('❌ Error cargando modelos:', error);
                this.$dispatch('notify', {
                    message: 'Error cargando modelos de IA. Verifica tu conexión.',
                    type: 'error'
                });
            }
        },
        
        async openCamera() {
            this.cameraOpen = true;
            this.status = { 
                text: 'Iniciando cámara...', 
                color: 'bg-yellow-500 text-white',
                loading: true 
            };
            
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { 
                        width: { ideal: 1280 }, 
                        height: { ideal: 720 }, 
                        facingMode: 'user' 
                    }
                });
                
                this.$refs.video.srcObject = this.stream;
                
                await new Promise(resolve => {
                    this.$refs.video.onloadedmetadata = resolve;
                });
                
                // Configurar canvas con las dimensiones del contenedor
                const video = this.$refs.video;
                const canvas = this.$refs.canvas;
                const container = canvas.parentElement;
                
                // Establecer dimensiones del canvas al contenedor
                canvas.width = container.offsetWidth;
                canvas.height = container.offsetHeight;
                
                console.log('Canvas configurado:', {
                    canvasWidth: canvas.width,
                    canvasHeight: canvas.height,
                    videoWidth: video.videoWidth,
                    videoHeight: video.videoHeight
                });
                
                this.status = { 
                    text: 'Cámara lista - Detectando...', 
                    color: 'bg-success-500 text-white',
                    loading: false 
                };
                
                // Iniciar detección en tiempo real
                this.startRealTimeDetection();
                
            } catch (error) {
                console.error('Error accediendo a cámara:', error);
                this.$dispatch('notify', {
                    message: 'No se puede acceder a la cámara. Verifica los permisos.',
                    type: 'error'
                });
                this.closeCamera();
            }
        },
        
        async startRealTimeDetection() {
            const video = this.$refs.video;
            const canvas = this.$refs.canvas;
            const ctx = canvas.getContext('2d');
            
            const detectFace = async () => {
                if (!this.cameraOpen || this.capturedImage) {
                    return;
                }
                
                try {
                    // Detectar solo el rostro sin landmarks
                    const detection = await faceapi
                        .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({
                            inputSize: 320,
                            scoreThreshold: 0.5
                        }));
                    
                    // Limpiar canvas
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    
                    if (detection) {
                        this.faceDetectedCount = 1;
                        this.detectionMessage = '✓ Rostro detectado - Mantén la posición';
                        
                        // Calcular el factor de escala entre el video y el canvas
                        const scaleX = canvas.width / video.videoWidth;
                        const scaleY = canvas.height / video.videoHeight;
                        
                        console.log('Escalas:', { scaleX, scaleY, canvasW: canvas.width, canvasH: canvas.height, videoW: video.videoWidth, videoH: video.videoHeight });
                        
                        // Escalar la detección
                        const scaledBox = {
                            x: detection.box.x * scaleX,
                            y: detection.box.y * scaleY,
                            width: detection.box.width * scaleX,
                            height: detection.box.height * scaleY
                        };
                        
                        console.log('Box escalado:', scaledBox);
                        
                        console.log('Dibujando recuadro en canvas...');
                        
                        // Dibujar solo la caja delimitadora con efecto neón
                        this.drawFaceBox(ctx, scaledBox);
                        
                        console.log('Dibujo completado');
                        
                    } else {
                        this.faceDetectedCount = 0;
                        this.detectionMessage = 'Posiciona tu rostro frente a la cámara';
                    }
                    
                } catch (error) {
                    console.error('Error en detección:', error);
                }
                
                // Continuar con la siguiente detección
                if (this.cameraOpen && !this.capturedImage) {
                    requestAnimationFrame(detectFace);
                }
            };
            
            // Iniciar loop de detección
            detectFace();
        },
        
        drawFaceBox(ctx, box) {
            const { x, y, width, height } = box;
            
            console.log('drawFaceBox llamado con:', box);
            
            // Guardar estado del contexto
            ctx.save();
            
            // Configurar estilo de línea - verde medio
            ctx.strokeStyle = '#10B981'; // Verde medio (verde esmeralda)
            ctx.lineWidth = 4;
            ctx.shadowBlur = 0; // Sin efecto de brillo
            
            // Dibujar rectángulo redondeado
            const radius = 15;
            ctx.beginPath();
            ctx.moveTo(x + radius, y);
            ctx.lineTo(x + width - radius, y);
            ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
            ctx.lineTo(x + width, y + height - radius);
            ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
            ctx.lineTo(x + radius, y + height);
            ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
            ctx.lineTo(x, y + radius);
            ctx.quadraticCurveTo(x, y, x + radius, y);
            ctx.closePath();
            ctx.stroke();
            
            // Dibujar esquinas decorativas
            const cornerLength = 30;
            ctx.lineWidth = 6;
            
            // Esquina superior izquierda
            ctx.beginPath();
            ctx.moveTo(x, y + cornerLength);
            ctx.lineTo(x, y);
            ctx.lineTo(x + cornerLength, y);
            ctx.stroke();
            
            // Esquina superior derecha
            ctx.beginPath();
            ctx.moveTo(x + width - cornerLength, y);
            ctx.lineTo(x + width, y);
            ctx.lineTo(x + width, y + cornerLength);
            ctx.stroke();
            
            // Esquina inferior izquierda
            ctx.beginPath();
            ctx.moveTo(x, y + height - cornerLength);
            ctx.lineTo(x, y + height);
            ctx.lineTo(x + cornerLength, y + height);
            ctx.stroke();
            
            // Esquina inferior derecha
            ctx.beginPath();
            ctx.moveTo(x + width - cornerLength, y + height);
            ctx.lineTo(x + width, y + height);
            ctx.lineTo(x + width, y + height - cornerLength);
            ctx.stroke();
            
            // Restaurar estado del contexto
            ctx.restore();
            
            console.log('drawFaceBox completado');
        },
        
        
        async capturePhoto() {
            console.log('Iniciando captura de foto...');
            
            this.status = { 
                text: 'Detectando rostro...', 
                color: 'bg-blue-500 text-white',
                loading: true 
            };
            
            try {
                const video = this.$refs.video;
                console.log('Video obtenido, iniciando detección...');
                
                const detection = await faceapi
                    .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                    .withFaceLandmarks()
                    .withFaceDescriptor();
                
                console.log('Detección completada:', detection ? 'Rostro detectado' : 'No se detectó rostro');
                
                if (!detection) {
                    this.$dispatch('notify', {
                        message: 'No se detectó ningún rostro. Asegúrate de estar frente a la cámara.',
                        type: 'warning'
                    });
                    this.status = { 
                        text: '❌ No se detectó rostro', 
                        color: 'bg-danger-500 text-white',
                        loading: false 
                    };
                    return;
                }
                
                this.faceDescriptors = Array.from(detection.descriptor);
                console.log('Descriptores extraídos:', this.faceDescriptors.length, 'dimensiones');
                
                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0);
                this.capturedImage = canvas.toDataURL('image/jpeg', 0.95);
                
                console.log('Imagen capturada, tamaño:', this.capturedImage.length, 'caracteres');
                
                this.status = { 
                    text: 'Rostro capturado correctamente', 
                    color: 'bg-success-500 text-white',
                    loading: false 
                };
                
            } catch (error) {
                console.error('Error capturando rostro:', error);
                this.$dispatch('notify', {
                    message: 'Error al procesar el rostro. Intenta de nuevo.',
                    type: 'error'
                });
            }
        },
        
        retakePhoto() {
            this.capturedImage = null;
            this.faceDescriptors = null;
            this.status = { 
                text: 'Cámara lista', 
                color: 'bg-success-500 text-white',
                loading: false 
            };
        },
        
        confirmPhoto() {
            // Buscar el campo DNI con diferentes selectores
            let dniInput = document.querySelector('input[name="dni"]') 
                        || document.querySelector('[data-field-name="dni"]')
                        || document.querySelector('#data\\.dni')
                        || document.querySelector('input[wire\\:model="data.dni"]');
            
            const dni = dniInput ? dniInput.value.trim() : '';
            
            console.log('Confirmando foto - DNI encontrado:', dni || 'NO ENCONTRADO');
            console.log('Campo DNI:', dniInput);
            
            if (!dni) {
                alert('Por favor, ingresa el DNI del empleado primero');
                return;
            }
            
            // Buscar los campos ocultos con diferentes selectores posibles
            let descriptorsInput = document.querySelector('input[name="face_descriptors"]') 
                                || document.querySelector('[data-field-name="face_descriptors"]')
                                || document.querySelector('#face_descriptors');
                                
            let pathInput = document.querySelector('input[name="foto_facial_path"]')
                         || document.querySelector('[data-field-name="foto_facial_path"]')
                         || document.querySelector('#foto_facial_path');
                         
            let imageInput = document.querySelector('input[name="captured_face_image"]')
                          || document.querySelector('[data-field-name="captured_face_image"]')
                          || document.querySelector('#captured_face_image');
            
            console.log('Buscando campos ocultos:', {
                descriptorsInput: !!descriptorsInput,
                pathInput: !!pathInput,
                imageInput: !!imageInput
            });
            
            // Si no existen, crearlos dinámicamente
            if (!descriptorsInput) {
                descriptorsInput = document.createElement('input');
                descriptorsInput.type = 'hidden';
                descriptorsInput.name = 'face_descriptors';
                descriptorsInput.setAttribute('wire:model', 'data.face_descriptors');
                document.querySelector('form').appendChild(descriptorsInput);
                console.log('Campo face_descriptors creado');
            }
            
            if (!pathInput) {
                pathInput = document.createElement('input');
                pathInput.type = 'hidden';
                pathInput.name = 'foto_facial_path';
                pathInput.setAttribute('wire:model', 'data.foto_facial_path');
                document.querySelector('form').appendChild(pathInput);
                console.log('Campo foto_facial_path creado');
            }
            
            if (!imageInput) {
                imageInput = document.createElement('input');
                imageInput.type = 'hidden';
                imageInput.name = 'captured_face_image';
                imageInput.setAttribute('wire:model', 'data.captured_face_image');
                document.querySelector('form').appendChild(imageInput);
                console.log('Campo captured_face_image creado');
            }
            
            // Guardar datos en campos ocultos
            const descriptorsJson = JSON.stringify(this.faceDescriptors);
            const photoPath = `empleados_rostros/Empleado_${dni}.jpg`;
            
            descriptorsInput.value = descriptorsJson;
            pathInput.value = photoPath;
            imageInput.value = this.capturedImage;
            
            // Disparar eventos de Livewire para sincronizar
            descriptorsInput.dispatchEvent(new Event('input', { bubbles: true }));
            pathInput.dispatchEvent(new Event('input', { bubbles: true }));
            imageInput.dispatchEvent(new Event('input', { bubbles: true }));
            
            // También intentar con el modelo de Livewire directamente
            if (window.Livewire) {
                const component = window.Livewire.find(this.$el.closest('[wire\\:id]')?.getAttribute('wire:id'));
                if (component) {
                    component.set('data.face_descriptors', descriptorsJson);
                    component.set('data.foto_facial_path', photoPath);
                    component.set('data.captured_face_image', this.capturedImage);
                    console.log('Datos sincronizados con Livewire');
                }
            }
            
            console.log('Datos guardados:', {
                descriptors_length: descriptorsJson.length,
                path: photoPath,
                image_length: this.capturedImage.length
            });
            
            this.closeCamera();
            
            alert('Rostro capturado correctamente. Ahora haz clic en "Guardar" para confirmar el registro del empleado.');
        },
        
        closeCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
            }
            if (this.detectionInterval) {
                clearInterval(this.detectionInterval);
            }
            this.cameraOpen = false;
            this.capturedImage = null;
            this.faceDescriptors = null;
            this.faceDetectedCount = 0;
            this.detectionMessage = 'Posiciona tu rostro frente a la cámara';
        },
        
        async deletePhoto() {
            if (!confirm('¿Estás seguro de que deseas eliminar el registro facial? Esta acción no se puede deshacer.')) {
                return;
            }
            
            const descriptorsInput = document.querySelector('input[name="face_descriptors"]');
            const pathInput = document.querySelector('input[name="foto_facial_path"]');
            const imageInput = document.querySelector('input[name="captured_face_image"]');
            
            if (descriptorsInput) descriptorsInput.value = '';
            if (pathInput) pathInput.value = '';
            if (imageInput) imageInput.value = '';
            
            this.$dispatch('notify', {
                message: 'Registro facial marcado para eliminar. Guarda el formulario para confirmar.',
                type: 'info'
            });
        }
    }
}
</script>

<style>
[x-cloak] { 
    display: none !important; 
}

/* Animación de pulso para el indicador de rostro detectado */
@keyframes pulse-glow {
    0%, 100% {
        box-shadow: 0 0 20px rgba(0, 255, 136, 0.4);
    }
    50% {
        box-shadow: 0 0 40px rgba(0, 255, 136, 0.8);
    }
}

.bg-green-500 {
    animation: pulse-glow 2s ease-in-out infinite;
}

/* Efecto de escaneo para el contenedor del video */
@keyframes scan-line {
    0% {
        top: 0;
        opacity: 0.8;
    }
    50% {
        opacity: 1;
    }
    100% {
        top: 100%;
        opacity: 0.8;
    }
}

/* Canvas con efecto de overlay */
canvas {
    mix-blend-mode: screen;
}
</style>
@endpush
