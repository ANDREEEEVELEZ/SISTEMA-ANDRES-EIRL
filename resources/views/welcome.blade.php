<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Andres EIRL - Acceso</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }
        
        .gradient-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        .card-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .logo-section {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            position: relative;
        }
        
        .logo-section::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 1px;
            background: linear-gradient(to bottom, transparent, #cbd5e0, transparent);
        }
        
        .pulse-animation {
            animation: pulse 3s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .btn-primary, .btn-secondary {
            width: 100%;
            max-width: 340px;
            padding: 18px 24px;
            border-radius: 18px;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 4px 15px rgba(16,185,129,0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            box-shadow: 0 4px 15px rgba(59,130,246,0.4);
        }

        .btn-primary:hover, .btn-secondary:hover {
            transform: translateY(-3px);
        }

        .icon-wrapper {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: rgba(255,255,255,0.2);
            display:flex;
            align-items:center;
            justify-content:center;
            font-size: 24px;
        }
    </style>
</head>

<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="relative z-10 w-full max-w-4xl">
        <div class="card-container rounded-3xl overflow-hidden">
            <div class="grid md:grid-cols-2 min-h-[600px]">
                
                {{-- IZQUIERDA --}}
                <div class="logo-section flex flex-col items-center justify-center p-8 md:p-12">
                    <div class="pulse-animation mb-6">
                        <img src="/images/AndresEIRL.png" class="w-32 h-32 md:w-48 md:h-48 drop-shadow-2xl">
                    </div>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-3 text-center">
                        Artesanal D'Andres
                    </h2>
                    <p class="text-gray-600 text-center text-sm md:text-base">
                        Sistema de Gestión Empresarial
                    </p>
                    <div class="mt-8 text-center">
                        <div class="inline-block bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-2 rounded-full text-sm font-semibold shadow-lg">
                            Since 2003
                        </div>
                    </div>
                </div>

                {{-- DERECHA --}}
                <div class="flex flex-col items-center justify-center p-8 md:p-12 bg-white">

                    <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-3 text-center">
                        Bienvenido
                    </h1>

                    <p class="text-gray-600 mb-10 text-lg text-center">
                        Seleccione una opción para continuar
                    </p>

                    {{-- SEPARACIÓN REAL (mb-6) --}}
                    <a href="{{ route('attendance.mark') }}"
                       class="btn-primary text-white mb-6">
                        <div class="flex items-center space-x-4">
                            <div class="icon-wrapper">✨</div>
                            <div class="text-left flex-1">
                                <div class="text-lg font-bold">Marcar Asistencia</div>
                                <div class="text-sm opacity-90">Reconocimiento Facial</div>
                            </div>
                            <span class="text-2xl">→</span>
                        </div>
                    </a>

                    <a href="/admin/login"
                       class="btn-secondary text-white">
                        <div class="flex items-center space-x-4">
                            <div class="icon-wrapper">🔐</div>
                            <div class="text-left flex-1">
                                <div class="text-lg font-bold">Ingresar al Sistema</div>
                                <div class="text-sm opacity-90">Panel de Administración</div>
                            </div>
                            <span class="text-2xl">→</span>
                        </div>
                    </a>

                    <div class="mt-12 pt-8 border-t border-gray-200 w-full text-center">
                        <p class="text-gray-500 text-sm">
                            © {{ date('Y') }} — Sistema de Gestión Andres EIRL
                        </p>
                        <p class="text-gray-400 text-xs mt-1">
                            Versión 2.0 - Powered by Laravel
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</body>
</html>
