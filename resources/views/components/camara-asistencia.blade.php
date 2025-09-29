<div class="camara-container">
    <div class="mb-4">
        <button 
            type="button" 
            id="btnAbrirCamara" 
            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
        >
            Abrir Cámara
        </button>
        
        <button 
            type="button" 
            id="btnCerrarCamara" 
            class="ml-2 inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
            style="display: none;"
        >
            Cerrar Cámara
        </button>
    </div>
    
    <!-- Video element para mostrar la cámara -->
    <div id="camaraVideo" style="display: none;" class="mb-4">
        <video 
            id="videoElement" 
            width="400" 
            height="300" 
            autoplay 
            muted
            class="border rounded-lg"
        ></video>
        
        <div class="mt-2">
            <button 
                type="button" 
                id="btnCapturar" 
                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
            >
                Capturar Foto
            </button>
        </div>
    </div>
    
    <!-- Canvas oculto para capturar la imagen -->
    <canvas id="canvasElement" style="display: none;"></canvas>
    
    <!-- Contenedor para mostrar la foto capturada -->
    <div id="fotoCapturada" style="display: none;" class="mb-4">
        <h4 class="text-sm font-medium text-gray-900 mb-2">Foto Capturada:</h4>
        <img id="imagenCapturada" class="border rounded-lg" width="200" height="150" />
        <div class="mt-2">
            <button 
                type="button" 
                id="btnNuevaFoto" 
                class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-900 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150"
            >
                Tomar Nueva Foto
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let video = document.getElementById('videoElement');
    let canvas = document.getElementById('canvasElement');
    let context = canvas.getContext('2d');
    let stream = null;
    
    const btnAbrirCamara = document.getElementById('btnAbrirCamara');
    const btnCerrarCamara = document.getElementById('btnCerrarCamara');
    const btnCapturar = document.getElementById('btnCapturar');
    const btnNuevaFoto = document.getElementById('btnNuevaFoto');
    const camaraVideo = document.getElementById('camaraVideo');
    const fotoCapturada = document.getElementById('fotoCapturada');
    const imagenCapturada = document.getElementById('imagenCapturada');
    
    // Abrir cámara
    btnAbrirCamara.addEventListener('click', async function() {
        try {
            // Solicitar acceso a la cámara
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                } 
            });
            
            video.srcObject = stream;
            
            // Mostrar video y botones
            camaraVideo.style.display = 'block';
            btnAbrirCamara.style.display = 'none';
            btnCerrarCamara.style.display = 'inline-flex';
            
            console.log('Cámara activada correctamente');
        } catch (error) {
            console.error('Error al acceder a la cámara:', error);
            alert('No se pudo acceder a la cámara. Asegúrese de dar los permisos necesarios.');
        }
    });
    
    // Cerrar cámara
    btnCerrarCamara.addEventListener('click', function() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        
        camaraVideo.style.display = 'none';
        fotoCapturada.style.display = 'none';
        btnAbrirCamara.style.display = 'inline-flex';
        btnCerrarCamara.style.display = 'none';
        
        console.log('Cámara cerrada');
    });
    
    // Capturar foto
    btnCapturar.addEventListener('click', function() {
        if (video.videoWidth === 0 || video.videoHeight === 0) {
            alert('La cámara no está lista. Espere un momento y vuelva a intentar.');
            return;
        }
        
        // Configurar el canvas con las dimensiones del video
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        // Dibujar el frame actual del video en el canvas
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        // Convertir el canvas a imagen
        const imageDataUrl = canvas.toDataURL('image/png');
        
        // Mostrar la imagen capturada
        imagenCapturada.src = imageDataUrl;
        fotoCapturada.style.display = 'block';
        
        console.log('Foto capturada correctamente');
        
        // Opcional: cerrar la cámara después de capturar
        // btnCerrarCamara.click();
    });
    
    // Tomar nueva foto
    btnNuevaFoto.addEventListener('click', function() {
        fotoCapturada.style.display = 'none';
        if (camaraVideo.style.display === 'none') {
            btnAbrirCamara.click();
        }
    });
});
</script>

<style>
.camara-container {
    padding: 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background-color: #f9fafb;
}

#videoElement {
    max-width: 100%;
    height: auto;
}
</style>