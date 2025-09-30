<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marcar Asistencia - Reconocimiento Facial</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .attendance-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            margin: 2rem auto;
            max-width: 600px;
        }
        
        .camera-container {
            position: relative;
            background: #000;
            border-radius: 15px;
            overflow: hidden;
            margin: 2rem 0;
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
            border: 3px dashed #00ff00;
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
        
        .status-scanning {
            background: rgba(255, 193, 7, 0.9) !important;
            color: #000 !important;
        }
        
        .status-success {
            background: rgba(40, 167, 69, 0.9) !important;
        }
        
        .status-error {
            background: rgba(220, 53, 69, 0.9) !important;
        }
        
        .btn-scan {
            background: linear-gradient(45deg, #00c851, #007e33);
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .btn-scan:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,200,81,0.3);
        }
        
        .btn-scan:disabled {
            background: #6c757d;
            transform: none;
            box-shadow: none;
        }
        
        .result-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
            text-align: center;
            display: none;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            margin: 20px 0;
        }
        
        .welcome-message {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .welcome-message h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .welcome-message p {
            color: #666;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="attendance-container">
            <div class="welcome-message">
                <h1><i class="fas fa-camera me-2"></i>Marcar Asistencia</h1>
                <p>Colócate frente a la cámara para registrar tu asistencia</p>
            </div>
            
            <div class="camera-container">
                <video id="video" autoplay muted></video>
                <div class="camera-overlay"></div>
                <div id="status" class="status-indicator">
                    <i class="fas fa-camera me-1"></i> Preparando cámara...
                </div>
            </div>
            
            <div class="text-center">
                <button id="scanBtn" class="btn btn-scan btn-lg text-white" disabled>
                    <i class="fas fa-search me-2"></i>Escanear Rostro
                </button>
            </div>
            
            <div class="loading-spinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Procesando...</span>
                </div>
                <p class="mt-2">Procesando reconocimiento facial...</p>
            </div>
            
            <div id="resultCard" class="result-card">
                <div id="resultContent"></div>
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
        class FaceAttendanceSystem {
            constructor() {
                this.video = document.getElementById('video');
                this.scanBtn = document.getElementById('scanBtn');
                this.status = document.getElementById('status');
                this.resultCard = document.getElementById('resultCard');
                this.resultContent = document.getElementById('resultContent');
                this.loadingSpinner = document.querySelector('.loading-spinner');
                
                this.isModelLoaded = false;
                this.isScanning = false;
                
                this.init();
            }
            
            async init() {
                try {
                    await this.loadModels();
                    await this.startCamera();
                    this.setupEventListeners();
                } catch (error) {
                    console.error('Error inicializando sistema:', error);
                    this.updateStatus('error', 'Error inicializando el sistema');
                }
            }
            
            async loadModels() {
                this.updateStatus('loading', 'Cargando modelos de IA...');
                
                const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.13/model';
                
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                ]);
                
                this.isModelLoaded = true;
                this.updateStatus('success', 'Sistema listo');
                this.scanBtn.disabled = false;
            }
            
            async startCamera() {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            width: { ideal: 640 },
                            height: { ideal: 480 },
                            facingMode: 'user'
                        }
                    });
                    
                    this.video.srcObject = stream;
                    
                    return new Promise((resolve) => {
                        this.video.onloadedmetadata = () => {
                            resolve();
                        };
                    });
                } catch (error) {
                    console.error('Error accediendo a la cámara:', error);
                    this.updateStatus('error', 'No se puede acceder a la cámara');
                    throw error;
                }
            }
            
            setupEventListeners() {
                this.scanBtn.addEventListener('click', () => {
                    this.scanFace();
                });
            }
            
            async scanFace() {
                if (!this.isModelLoaded || this.isScanning) return;
                
                this.isScanning = true;
                this.scanBtn.disabled = true;
                this.showLoading(true);
                this.hideResult();
                this.updateStatus('scanning', 'Escaneando rostro...');
                
                try {
                    // Detectar rostro y extraer descriptores
                    const detection = await faceapi
                        .detectSingleFace(this.video, new faceapi.TinyFaceDetectorOptions())
                        .withFaceLandmarks()
                        .withFaceDescriptor();
                    
                    if (!detection) {
                        throw new Error('No se detectó ningún rostro. Asegúrate de estar bien posicionado frente a la cámara.');
                    }
                    
                    // Enviar descriptores al servidor
                    const response = await this.sendToServer(Array.from(detection.descriptor));
                    
                    if (response.data.success) {
                        this.showSuccess(response.data);
                    } else {
                        this.showError(response.data.message);
                    }
                    
                } catch (error) {
                    console.error('Error en reconocimiento facial:', error);
                    this.showError(error.message || 'Error procesando el reconocimiento facial');
                } finally {
                    this.isScanning = false;
                    this.scanBtn.disabled = false;
                    this.showLoading(false);
                    this.updateStatus('success', 'Sistema listo');
                }
            }
            
            async sendToServer(descriptors) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                return await axios.post('/face-recognition/mark-attendance', {
                    face_descriptors: descriptors
                }, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    }
                });
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
            
            showLoading(show) {
                this.loadingSpinner.style.display = show ? 'block' : 'none';
            }
            
            showSuccess(data) {
                this.resultContent.innerHTML = `
                    <div class="text-success">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <h4>¡${data.tipo === 'entrada' ? 'Entrada' : 'Salida'} registrada!</h4>
                        <p class="mb-1"><strong>Empleado:</strong> ${data.empleado}</p>
                        <p class="mb-0"><strong>Hora:</strong> ${data.hora}</p>
                        <small class="text-muted">${data.message}</small>
                    </div>
                `;
                this.showResult();
                
                // Auto-ocultar después de 5 segundos
                setTimeout(() => this.hideResult(), 5000);
            }
            
            showError(message) {
                this.resultContent.innerHTML = `
                    <div class="text-danger">
                        <i class="fas fa-times-circle fa-3x mb-3"></i>
                        <h4>Error</h4>
                        <p>${message}</p>
                    </div>
                `;
                this.showResult();
            }
            
            showResult() {
                this.resultCard.style.display = 'block';
            }
            
            hideResult() {
                this.resultCard.style.display = 'none';
            }
        }
        
        // Inicializar el sistema cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', () => {
            new FaceAttendanceSystem();
        });
    </script>
</body>
</html>