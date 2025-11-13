<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Facial - Empleados</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .registration-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            margin: 2rem auto;
            max-width: 800px;
        }
        
        .camera-container {
            position: relative;
            background: #000;
            border-radius: 15px;
            overflow: hidden;
            margin: 2rem 0;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        #video {
            width: 100%;
            height: 400px;
            object-fit: cover;
        }
        
        .camera-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            border: 3px dashed #007bff;
            border-radius: 50%;
            pointer-events: none;
        }
        
        .status-indicator {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .employee-selector {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .employee-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .employee-card:hover {
            border-color: #007bff;
            background: #f0f8ff;
        }
        
        .employee-card.selected {
            border-color: #007bff;
            background: #e7f3ff;
        }
        
        .employee-card.registered {
            border-color: #28a745;
            background: #f0fff4;
        }
        
        .btn-register {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            padding: 12px 25px;
            font-size: 16px;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0,123,255,0.3);
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .step {
            display: flex;
            align-items: center;
            margin: 0 10px;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #dee2e6;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 8px;
        }
        
        .step.active .step-number {
            background: #007bff;
            color: white;
        }
        
        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="registration-container">
            <div class="text-center mb-4">
                <h1><i class="fas fa-user-plus me-2"></i>Registro Facial de Empleados</h1>
                <p class="text-muted">Registra el rostro de los empleados para el sistema de asistencia</p>
            </div>
            
            <!-- Indicador de pasos -->
            <div class="step-indicator">
                <div class="step active" id="step1">
                    <div class="step-number">1</div>
                    <span>Seleccionar Empleado</span>
                </div>
                <div class="step" id="step2">
                    <div class="step-number">2</div>
                    <span>Capturar Rostro</span>
                </div>
                <div class="step" id="step3">
                    <div class="step-number">3</div>
                    <span>Confirmar Registro</span>
                </div>
            </div>
            
            <!-- Selector de empleado -->
            <div class="employee-selector" id="employeeSelector">
                <h5><i class="fas fa-users me-2"></i>Selecciona un empleado:</h5>
                <div id="employeeList" class="mt-3">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Cargando empleados...</span>
                        </div>
                        <p class="mt-2">Cargando lista de empleados...</p>
                    </div>
                </div>
            </div>
            
            <!-- Contenedor de cámara (inicialmente oculto) -->
            <div class="camera-section" id="cameraSection" style="display: none;">
                <div class="camera-container">
                    <video id="video" autoplay muted></video>
                    <div class="camera-overlay"></div>
                    <div id="status" class="status-indicator">
                        <i class="fas fa-camera me-1"></i> Preparando cámara...
                    </div>
                </div>
                
                <div class="text-center">
                    <button id="captureBtn" class="btn btn-register btn-lg text-white me-3" disabled>
                        <i class="fas fa-camera me-2"></i>Capturar Rostro
                    </button>
                    <button id="backBtn" class="btn btn-secondary btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </button>
                </div>
            </div>
            
            <!-- Sección de confirmación -->
            <div class="confirmation-section" id="confirmationSection" style="display: none;">
                <div class="text-center">
                    <div id="capturedImage" class="mb-3"></div>
                    <div id="confirmationContent"></div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="{{ route('face.attendance') }}" class="btn btn-outline-primary">
                    <i class="fas fa-clock me-2"></i>Ir a Marcar Asistencia
                </a>
            </div>
        </div>
    </div>

    <!-- Face-api.js versión específica que funciona -->
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.13/dist/face-api.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script>
        class FaceRegistrationSystem {
            constructor() {
                this.video = document.getElementById('video');
                this.captureBtn = document.getElementById('captureBtn');
                this.backBtn = document.getElementById('backBtn');
                this.status = document.getElementById('status');
                
                this.selectedEmployee = null;
                this.isModelLoaded = false;
                this.currentStep = 1;
                
                this.init();
            }
            
            async init() {
                try {
                    await this.loadEmployees();
                    await this.loadModels();
                    this.setupEventListeners();
                } catch (error) {
                    console.error('Error inicializando sistema:', error);
                }
            }
            
            async loadEmployees() {
                try {
                    const response = await axios.get('/face-recognition/employees');
                    const employees = response.data.empleados;
                    
                    const employeeList = document.getElementById('employeeList');
                    
                    if (employees.length === 0) {
                        employeeList.innerHTML = `
                            <div class="text-center text-muted">
                                <i class="fas fa-users fa-3x mb-3"></i>
                                <p>No hay empleados registrados en el sistema</p>
                            </div>
                        `;
                        return;
                    }
                    
                    employeeList.innerHTML = employees.map(employee => `
                        <div class="employee-card ${employee.tiene_rostro_registrado ? 'registered' : ''}" 
                             data-id="${employee.id}" data-name="${employee.nombre_completo}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">${employee.nombre_completo}</h6>
                                    <small class="text-muted">ID: ${employee.id}</small>
                                </div>
                                <div>
                                    ${employee.tiene_rostro_registrado ? 
                                        '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Registrado</span>' : 
                                        '<span class="badge bg-warning"><i class="fas fa-clock me-1"></i>Pendiente</span>'
                                    }
                                </div>
                            </div>
                        </div>
                    `).join('');
                    
                    // Agregar event listeners a las tarjetas
                    document.querySelectorAll('.employee-card').forEach(card => {
                        card.addEventListener('click', () => {
                            this.selectEmployee(card);
                        });
                    });
                    
                } catch (error) {
                    console.error('Error cargando empleados:', error);
                    document.getElementById('employeeList').innerHTML = `
                        <div class="text-center text-danger">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                            <p>Error cargando la lista de empleados</p>
                        </div>
                    `;
                }
            }
            
            selectEmployee(card) {
                // Remover selección anterior
                document.querySelectorAll('.employee-card').forEach(c => c.classList.remove('selected'));
                
                // Seleccionar nueva tarjeta
                card.classList.add('selected');
                
                this.selectedEmployee = {
                    id: card.dataset.id,
                    name: card.dataset.name
                };
                
                // Mostrar botón para continuar
                this.showContinueButton();
            }
            
            showContinueButton() {
                const employeeList = document.getElementById('employeeList');
                let continueBtn = document.getElementById('continueBtn');
                
                if (!continueBtn) {
                    continueBtn = document.createElement('div');
                    continueBtn.id = 'continueBtn';
                    continueBtn.className = 'text-center mt-3';
                    continueBtn.innerHTML = `
                        <button class="btn btn-primary btn-lg" onclick="faceRegistration.proceedToCapture()">
                            <i class="fas fa-arrow-right me-2"></i>Continuar con ${this.selectedEmployee.name}
                        </button>
                    `;
                    employeeList.parentNode.appendChild(continueBtn);
                }
            }
            
            async loadModels() {
                console.log('🤖 Iniciando carga de modelos de IA...');
                this.updateStatus('loading', 'Cargando modelos de IA...');
                
                // Usar CDN de @vladmandic/face-api que incluye los modelos
                const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.13/model';
                
                try {
                    console.log('📥 Cargando TinyFaceDetector...');
                    await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                    
                    console.log('📥 Cargando FaceLandmark68Net...');
                    await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                    
                    console.log('📥 Cargando FaceRecognitionNet...');
                    await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
                    
                    this.isModelLoaded = true;
                    console.log('✅ Todos los modelos cargados correctamente');
                    this.updateStatus('success', 'Modelos cargados - Sistema listo');
                    
                } catch (error) {
                    console.error('❌ Error cargando modelos:', error);
                    this.updateStatus('error', 'Error cargando modelos de IA');
                    alert('Error cargando modelos de IA. Verifica tu conexión a internet.');
                }
            }
            
            async proceedToCapture() {
                if (!this.selectedEmployee) return;
                
                this.setStep(2);
                document.getElementById('employeeSelector').style.display = 'none';
                document.getElementById('cameraSection').style.display = 'block';
                
                await this.startCamera();
            }
            
            async startCamera() {
                try {
                    this.updateStatus('loading', 'Iniciando cámara...');
                    
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            width: { ideal: 640 },
                            height: { ideal: 480 },
                            facingMode: 'user'
                        }
                    });
                    
                    this.video.srcObject = stream;
                    
                    this.video.onloadedmetadata = () => {
                        this.updateStatus('success', 'Cámara lista - Posiciónate frente a la cámara');
                        this.captureBtn.disabled = false;
                    };
                    
                } catch (error) {
                    console.error('Error accediendo a la cámara:', error);
                    this.updateStatus('error', 'No se puede acceder a la cámara');
                }
            }
            
            setupEventListeners() {
                this.captureBtn.addEventListener('click', () => {
                    this.captureAndRegisterFace();
                });
                
                this.backBtn.addEventListener('click', () => {
                    this.goBack();
                });
            }
            
            async captureAndRegisterFace() {
                console.log('🔥 Iniciando captura de rostro...');
                console.log('Modelo cargado:', this.isModelLoaded);
                console.log('Empleado seleccionado:', this.selectedEmployee);
                
                if (!this.isModelLoaded) {
                    alert('Los modelos de IA aún no se han cargado. Espera un momento e intenta de nuevo.');
                    return;
                }
                
                if (!this.selectedEmployee) {
                    alert('No has seleccionado un empleado.');
                    return;
                }
                
                this.captureBtn.disabled = true;
                this.updateStatus('scanning', 'Procesando rostro...');
                
                try {
                    console.log('🎥 Detectando rostro en el video...');
                    
                    // Detectar rostro y extraer descriptores
                    const detection = await faceapi
                        .detectSingleFace(this.video, new faceapi.TinyFaceDetectorOptions())
                        .withFaceLandmarks()
                        .withFaceDescriptor();
                    
                    console.log('👤 Detección resultado:', detection);
                    
                    if (!detection) {
                        throw new Error('No se detectó ningún rostro. Asegúrate de estar bien posicionado frente a la cámara.');
                    }
                    
                    console.log('✅ Rostro detectado, extrayendo imagen...');
                    
                    // Capturar imagen
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    canvas.width = this.video.videoWidth;
                    canvas.height = this.video.videoHeight;
                    ctx.drawImage(this.video, 0, 0);
                    const imageData = canvas.toDataURL('image/jpeg', 0.8);
                    
                    console.log('📤 Enviando datos al servidor...');
                    
                    // Enviar al servidor
                    const response = await this.sendToServer(Array.from(detection.descriptor), imageData);
                    
                    console.log('📨 Respuesta del servidor:', response);
                    
                    if (response.data.success) {
                        this.showSuccess(imageData);
                    } else {
                        throw new Error(response.data.message || 'Error registrando el rostro');
                    }
                    
                } catch (error) {
                    console.error('❌ Error capturando rostro:', error);
                    this.updateStatus('error', error.message);
                    alert('Error: ' + error.message);
                } finally {
                    this.captureBtn.disabled = false;
                }
            }
            
            async sendToServer(descriptors, imageData) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                return await axios.post('/face-recognition/register-face', {
                    empleado_id: this.selectedEmployee.id,
                    face_descriptors: descriptors,
                    photo: imageData
                }, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    }
                });
            }
            
            showSuccess(imageData) {
                this.setStep(3);
                document.getElementById('cameraSection').style.display = 'none';
                document.getElementById('confirmationSection').style.display = 'block';
                
                document.getElementById('capturedImage').innerHTML = `
                    <img src="${imageData}" alt="Rostro capturado" class="img-thumbnail" style="width: 200px; height: 200px; object-fit: cover; border-radius: 50%; border: 4px solid #28a745; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
                `;
                
                document.getElementById('confirmationContent').innerHTML = `
                    <div class="text-success">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <h4>¡Rostro registrado exitosamente!</h4>
                        <p class="mb-3"><strong>Empleado:</strong> ${this.selectedEmployee.name}</p>
                        <p class="text-muted">El empleado ya puede usar el sistema de reconocimiento facial para marcar asistencia.</p>
                        <div class="mt-4">
                            <button class="btn btn-primary me-2" onclick="location.reload()">
                                <i class="fas fa-plus me-2"></i>Registrar Otro
                            </button>
                            <a href="{{ route('face.attendance') }}" class="btn btn-success">
                                <i class="fas fa-clock me-2"></i>Probar Asistencia
                            </a>
                        </div>
                    </div>
                `;
            }
            
            goBack() {
                if (this.currentStep === 2) {
                    this.setStep(1);
                    document.getElementById('cameraSection').style.display = 'none';
                    document.getElementById('employeeSelector').style.display = 'block';
                    
                    // Detener cámara
                    if (this.video.srcObject) {
                        this.video.srcObject.getTracks().forEach(track => track.stop());
                        this.video.srcObject = null;
                    }
                }
            }
            
            setStep(step) {
                this.currentStep = step;
                
                // Actualizar indicadores de pasos
                for (let i = 1; i <= 3; i++) {
                    const stepEl = document.getElementById(`step${i}`);
                    stepEl.classList.remove('active', 'completed');
                    
                    if (i < step) {
                        stepEl.classList.add('completed');
                    } else if (i === step) {
                        stepEl.classList.add('active');
                    }
                }
            }
            
            updateStatus(type, message) {
                this.status.className = `status-indicator status-${type}`;
                this.status.innerHTML = `<i class="fas fa-${this.getStatusIcon(type)} me-1"></i> ${message}`;
            }
            
            getStatusIcon(type) {
                const icons = {
                    loading: 'spinner fa-spin',
                    success: 'check',
                    error: 'exclamation-triangle',
                    scanning: 'search'
                };
                return icons[type] || 'camera';
            }
        }
        
        // Crear instancia global
        let faceRegistration;
        
        document.addEventListener('DOMContentLoaded', () => {
            faceRegistration = new FaceRegistrationSystem();
        });
    </script>
</body>
</html>