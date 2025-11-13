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
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-2xl transform transition-all max-w-4xl w-full">
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
                            
                            <!-- Círculo de seguimiento facial dinámico -->
                            <div 
                                x-ref="faceOverlay"
                                class="face-tracking-overlay"
                                :class="{
                                    'tracking': faceDetectedCount > 0,
                                    'no-face': faceDetectedCount === 0
                                }"
                            ></div>
                        </div>
                        
                        <!-- Instrucciones con guía de posición -->
                        <div class="absolute bottom-4 left-4 right-4 bg-black/80 backdrop-blur-sm rounded-lg px-4 py-3 border border-cyan-400/30">
                            <p class="text-white text-sm text-center font-medium">
                                <span x-text="detectionMessage"></span>
                            </p>
                            <p 
                                x-show="guidanceMessage" 
                                x-text="guidanceMessage"
                                class="text-xs text-center mt-1"
                                :class="{
                                    'text-green-400': guidanceType === 'success',
                                    'text-yellow-400': guidanceType === 'warning',
                                    'text-cyan-400': guidanceType === 'info'
                                }"
                            ></p>
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
                                    style="width: 200px; height: 200px; border-radius: 50%; object-fit: cover; border: 4px solid #10b981; box-shadow: 0 10px 25px rgba(0,0,0,0.15);"
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
                    <div class="flex flex-wrap justify-end items-center pt-4 px-4" style="gap: 2rem;">
                        <!-- Botón Cancelar (Rojo) - Mismo estilo que Registrar Rostro -->
                        <button
                            type="button"
                            @click="closeCamera()"
                            class="inline-flex items-center justify-center gap-2"
                            style="min-width: 160px; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important; border: 2px solid rgba(239, 68, 68, 0.3); border-radius: 0.75rem; font-weight: 600; font-size: 0.875rem; color: white !important; box-shadow: 0 3px 10px rgba(239, 68, 68, 0.4); cursor: pointer; transition: all 0.3s ease; white-space: nowrap;"
                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 5px 15px rgba(239, 68, 68, 0.5)'; this.style.background='linear-gradient(135deg, #ef4444 0%, #dc2626 100%)'"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 3px 10px rgba(239, 68, 68, 0.4)'; this.style.background='linear-gradient(135deg, #ef4444 0%, #dc2626 100%)'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 18px; height: 18px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Cancelar</span>
                        </button>
                        
                        <!-- Botón Capturar Rostro (Verde) - Mismo estilo que Registrar Rostro - Solo visible cuando NO hay imagen capturada -->
                        <button
                            type="button"
                            @click="capturePhoto()"
                            x-show="!capturedImage"
                            x-transition
                            :disabled="!modelsLoaded"
                            class="inline-flex items-center justify-center gap-2"
                            style="min-width: 180px;"
                            :style="{
                                padding: '0.75rem 1.5rem',
                                background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                                border: '2px solid rgba(16, 185, 129, 0.3)',
                                borderRadius: '0.75rem',
                                fontWeight: '600',
                                fontSize: '0.875rem',
                                color: 'white',
                                boxShadow: '0 3px 10px rgba(16, 185, 129, 0.4)',
                                cursor: !modelsLoaded ? 'not-allowed' : 'pointer',
                                transition: 'all 0.3s ease',
                                whiteSpace: 'nowrap',
                                opacity: !modelsLoaded ? '0.5' : '1'
                            }"
                            @mouseenter="if(modelsLoaded) { $el.style.transform='translateY(-2px)'; $el.style.boxShadow='0 5px 15px rgba(16, 185, 129, 0.5)'; }"
                            @mouseleave="$el.style.transform='translateY(0)'; $el.style.boxShadow='0 3px 10px rgba(16, 185, 129, 0.4)'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 18px; height: 18px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                            </svg>
                            <span>Capturar Rostro</span>
                        </button>
                        
                        <!-- Botón Volver a Tomar (Amarillo) - Solo visible cuando hay imagen capturada -->
                        <button
                            type="button"
                            @click="retakePhoto()"
                            x-show="capturedImage"
                            x-transition
                            class="inline-flex items-center justify-center gap-2"
                            style="min-width: 180px;"
                            :style="{
                                padding: '0.75rem 1.5rem',
                                background: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
                                border: '2px solid rgba(245, 158, 11, 0.3)',
                                borderRadius: '0.75rem',
                                fontWeight: '600',
                                fontSize: '0.875rem',
                                color: 'white',
                                boxShadow: '0 3px 10px rgba(245, 158, 11, 0.4)',
                                cursor: 'pointer',
                                transition: 'all 0.3s ease',
                                whiteSpace: 'nowrap'
                            }"
                            @mouseenter="$el.style.transform='translateY(-2px)'; $el.style.boxShadow='0 5px 15px rgba(245, 158, 11, 0.5)'"
                            @mouseleave="$el.style.transform='translateY(0)'; $el.style.boxShadow='0 3px 10px rgba(245, 158, 11, 0.4)'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 18px; height: 18px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            <span>Volver a Tomar</span>
                        </button>
                        
                        <!-- Botón Confirmar y Guardar (Verde) - Solo visible cuando hay imagen capturada -->
                        <button
                            type="button"
                            @click="confirmPhoto()"
                            x-show="capturedImage"
                            x-transition
                            class="inline-flex items-center justify-center gap-2"
                            style="min-width: 200px;"
                            :style="{
                                padding: '0.75rem 1.5rem',
                                background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                                border: '2px solid rgba(16, 185, 129, 0.3)',
                                borderRadius: '0.75rem',
                                fontWeight: '600',
                                fontSize: '0.875rem',
                                color: 'white',
                                boxShadow: '0 3px 10px rgba(16, 185, 129, 0.4)',
                                cursor: 'pointer',
                                transition: 'all 0.3s ease',
                                whiteSpace: 'nowrap'
                            }"
                            @mouseenter="$el.style.transform='translateY(-2px)'; $el.style.boxShadow='0 5px 15px rgba(16, 185, 129, 0.5)'"
                            @mouseleave="$el.style.transform='translateY(0)'; $el.style.boxShadow='0 3px 10px rgba(16, 185, 129, 0.4)'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 18px; height: 18px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Confirmar y Guardar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* ESTILOS DIRECTOS PARA ARREGLAR DIMENSIONES DE ROSTROS */
    .face-registration-component img[alt*="Vista previa"],
    img[alt*="Vista previa"],
    img[alt*="Rostro capturado"],
    [x-show="capturedImage"] img {
        width: 200px !important;
        height: 200px !important;
        max-width: 200px !important;
        max-height: 200px !important;
        min-width: 200px !important;
        min-height: 200px !important;
        border-radius: 50% !important;
        object-fit: cover !important;
        display: block !important;
        margin: 0 auto !important;
    }
    
    video[x-ref="video"] {
        width: 100% !important;
        height: 500px !important;
        object-fit: cover !important;
    }
    
    canvas[x-ref="canvas"] {
        width: 100% !important;
        height: 500px !important;
    }
</style>
@endpush

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
        guidanceMessage: '',
        guidanceType: 'info',
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
            const faceOverlay = this.$refs.faceOverlay;
            
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
                        
                        // Escalar la detección
                        const scaledBox = {
                            x: detection.box.x * scaleX,
                            y: detection.box.y * scaleY,
                            width: detection.box.width * scaleX,
                            height: detection.box.height * scaleY
                        };
                        
                        // Actualizar posición del círculo de seguimiento
                        this.updateOverlayPosition(scaledBox, canvas);
                        
                        // Proporcionar guía de posición
                        this.provideFaceGuidance(scaledBox, canvas);
                        
                    } else {
                        this.faceDetectedCount = 0;
                        this.detectionMessage = 'Posiciona tu rostro frente a la cámara';
                        this.guidanceMessage = '';
                        this.resetOverlayPosition();
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
        
        updateOverlayPosition(box, canvas) {
            const faceOverlay = this.$refs.faceOverlay;
            
            // Calcular el centro del rostro detectado
            const centerX = box.x + box.width / 2;
            const centerY = box.y + box.height / 2;
            
            // Calcular el tamaño del círculo basado en el tamaño del rostro
            const faceSize = Math.max(box.width, box.height);
            const overlaySize = Math.min(Math.max(faceSize * 1.3, 150), 350); // Entre 150px y 350px
            
            // Mover el overlay a la posición del rostro
            faceOverlay.style.left = `${centerX}px`;
            faceOverlay.style.top = `${centerY}px`;
            faceOverlay.style.width = `${overlaySize}px`;
            faceOverlay.style.height = `${overlaySize}px`;
        },
        
        resetOverlayPosition() {
            const faceOverlay = this.$refs.faceOverlay;
            faceOverlay.style.left = '50%';
            faceOverlay.style.top = '50%';
            faceOverlay.style.width = '200px';
            faceOverlay.style.height = '200px';
        },
        
        provideFaceGuidance(box, canvas) {
            const centerX = box.x + box.width / 2;
            const centerY = box.y + box.height / 2;
            const canvasCenterX = canvas.width / 2;
            const canvasCenterY = canvas.height / 2;
            
            const offsetX = centerX - canvasCenterX;
            const offsetY = centerY - canvasCenterY;
            
            const threshold = 50; // píxeles de tolerancia
            
            if (Math.abs(offsetX) < threshold && Math.abs(offsetY) < threshold) {
                this.guidanceMessage = '¡Posición perfecta! ✓';
                this.guidanceType = 'success';
            } else {
                let direction = '';
                if (Math.abs(offsetX) > threshold) {
                    direction = offsetX > 0 ? 'Muévete a la izquierda' : 'Muévete a la derecha';
                }
                if (Math.abs(offsetY) > threshold) {
                    if (direction) direction += ' y ';
                    direction += offsetY > 0 ? 'hacia arriba' : 'hacia abajo';
                }
                this.guidanceMessage = direction;
                this.guidanceType = 'warning';
            }
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
            this.guidanceMessage = '';
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

/* Círculo de seguimiento facial dinámico */
.face-tracking-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 200px;
    height: 200px;
    border: 3px solid #10b981;
    border-radius: 50%;
    pointer-events: none;
    transition: all 0.3s ease-out;
    box-shadow: 0 0 20px rgba(16, 185, 129, 0.5),
                inset 0 0 20px rgba(16, 185, 129, 0.2);
    z-index: 20;
}

.face-tracking-overlay.tracking {
    border-color: #10b981;
    animation: pulse-tracking 1.5s ease-in-out infinite;
}

.face-tracking-overlay.no-face {
    border-color: #f59e0b;
    border-style: dashed;
    box-shadow: 0 0 20px rgba(245, 158, 11, 0.5);
}

@keyframes pulse-tracking {
    0%, 100% {
        box-shadow: 0 0 20px rgba(16, 185, 129, 0.5),
                    inset 0 0 20px rgba(16, 185, 129, 0.2);
    }
    50% {
        box-shadow: 0 0 30px rgba(16, 185, 129, 0.8),
                    inset 0 0 30px rgba(16, 185, 129, 0.4);
    }
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
