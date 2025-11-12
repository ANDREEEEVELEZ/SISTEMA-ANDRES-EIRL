# ============================================================================
# SCRIPT DE VERIFICACIÓN Y CORRECCIÓN DEL THEME DE FILAMENT
# Proyecto: SISTEMA-ANDRES-EIRL
# ============================================================================

Write-Host "╔════════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║         VERIFICACIÓN DEL SISTEMA DE THEMES - FILAMENT             ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Variables
$errorCount = 0
$warningCount = 0

# ============================================================================
# 1. VERIFICAR ARCHIVOS FUENTE
# ============================================================================
Write-Host " 1. VERIFICANDO ARCHIVOS FUENTE..." -ForegroundColor Yellow
Write-Host ""

$sourceCss = "resources\css\filament\dashboard\theme.css"
if (Test-Path $sourceCss) {
    $size = (Get-Item $sourceCss).Length
    Write-Host "   theme.css fuente existe ($size bytes)" -ForegroundColor Green
} else {
    Write-Host "   theme.css fuente NO EXISTE" -ForegroundColor Red
    $errorCount++
}

$viteConfig = "vite.config.js"
if (Test-Path $viteConfig) {
    $content = Get-Content $viteConfig -Raw
    if ($content -match "resources/css/filament/dashboard/theme.css") {
        Write-Host " vite.config.js incluye theme.css" -ForegroundColor Green
    } else {
        Write-Host "   vite.config.js NO incluye theme.css" -ForegroundColor Red
        $errorCount++
    }
} else {
    Write-Host "  vite.config.js NO EXISTE" -ForegroundColor Red
    $errorCount++
}

$tailwindConfig = "tailwind.config.js"
if (Test-Path $tailwindConfig) {
    $content = Get-Content $tailwindConfig -Raw
    if ($content -match "fi-sidebar") {
        Write-Host "    tailwind.config.js tiene safelist configurado" -ForegroundColor Green
    } else {
        Write-Host "    tailwind.config.js podría necesitar safelist" -ForegroundColor Yellow
        $warningCount++
    }
}

# ============================================================================
# 2. VERIFICAR COMPILACIÓN DE VITE
# ============================================================================
Write-Host ""
Write-Host "🔨 2. VERIFICANDO COMPILACIÓN DE VITE..." -ForegroundColor Yellow
Write-Host ""

$manifest = "public\build\manifest.json"
if (Test-Path $manifest) {
    Write-Host "   manifest.json existe" -ForegroundColor Green

    $manifestContent = Get-Content $manifest | ConvertFrom-Json
    if ($manifestContent.'resources/css/filament/dashboard/theme.css') {
        $compiledFile = $manifestContent.'resources/css/filament/dashboard/theme.css'.file
        Write-Host "  theme.css está en manifest: $compiledFile" -ForegroundColor Green

        $compiledPath = "public\build\$compiledFile"
        if (Test-Path $compiledPath) {
            $compiledSize = (Get-Item $compiledPath).Length
            Write-Host "   Archivo compilado existe ($compiledSize bytes)" -ForegroundColor Green

            # Verificar contenido
            $compiledContent = Get-Content $compiledPath -Raw
            if ($compiledContent match "#8a2e4a") {
                Write-Host "   CSS compilado contiene color del sidebar" -ForegroundColor Green
            } else {
                Write-Host "   CSS compilado NO contiene el color esperado" -ForegroundColor Red
                $errorCount++
            }
        } else {
            Write-Host "   ❌ Archivo compilado NO EXISTE: $compiledPath" -ForegroundColor Red
            $errorCount++
        }
    } else {
        Write-Host "   theme.css NO está en manifest" -ForegroundColor Red
        Write-Host "   Ejecuta: npm run build" -ForegroundColor Cyan
        $errorCount++
    }
} else {
    Write-Host "   manifest.json NO EXISTE" -ForegroundColor Red
    Write-Host "  Ejecuta: npm run build" -ForegroundColor Cyan
    $errorCount++
}

# ============================================================================
# 3. VERIFICAR PROVIDER DE FILAMENT
# ============================================================================
Write-Host ""
Write-Host "⚙️  3. VERIFICANDO ADMINPANELPROVIDER..." -ForegroundColor Yellow
Write-Host ""

$provider = "app\Providers\Filament\AdminPanelProvider.php"
if (Test-Path $provider) {
    $content = Get-Content $provider -Raw

    if ($content -match "viteTheme") {
        Write-Host "   Provider usa viteTheme()" -ForegroundColor Green
    } elseif ($content -match "->theme\(") {
        Write-Host "     Provider usa theme() en lugar de viteTheme()" -ForegroundColor Yellow
        Write-Host "    Considera cambiar a ->viteTheme('resources/css/filament/dashboard/theme.css')" -ForegroundColor Cyan
        $warningCount++
    } else {
        Write-Host "    Provider NO carga ningún theme" -ForegroundColor Red
        $errorCount++
    }

    if ($content -match "->colors\(") {
        Write-Host "    Provider define colores primarios" -ForegroundColor Green
    } else {
        Write-Host "     Provider no define colores primarios" -ForegroundColor Yellow
        $warningCount++
    }
} else {
    Write-Host "    AdminPanelProvider NO EXISTE" -ForegroundColor Red
    $errorCount++
}

# ============================================================================
# 4. DETECTAR ARCHIVOS OBSOLETOS
# ============================================================================
Write-Host ""
Write-Host "  4. DETECTANDO ARCHIVOS OBSOLETOS..." -ForegroundColor Yellow
Write-Host ""

$obsoletePath = "public\css\filament\dashboard\theme.css"
if (Test-Path $obsoletePath) {
    Write-Host "     Archivo obsoleto detectado: $obsoletePath" -ForegroundColor Yellow
    Write-Host "    Este archivo probablemente no se usa (Vite carga desde public/build/)" -ForegroundColor Cyan
    $warningCount++

    Write-Host ""
    $delete = Read-Host "    ¿Deseas eliminar el archivo obsoleto? (s/n)"
    if ($delete -eq "s" -or $delete -eq "S") {
        Remove-Item $obsoletePath -Force
        Write-Host "    Archivo eliminado" -ForegroundColor Green
    }
}

# ============================================================================
# 5. VERIFICAR NODE_MODULES Y PACKAGE.JSON
# ============================================================================
Write-Host ""
Write-Host " 5. VERIFICANDO DEPENDENCIAS NPM..." -ForegroundColor Yellow
Write-Host ""

if (Test-Path "package.json") {
    Write-Host "   package.json existe" -ForegroundColor Green

    if (Test-Path "node_modules") {
        Write-Host "    node_modules instalado" -ForegroundColor Green
    } else {
        Write-Host "    node_modules NO EXISTE" -ForegroundColor Red
        Write-Host "    Ejecuta: npm install" -ForegroundColor Cyan
        $errorCount++
    }
} else {
    Write-Host "   package.json NO EXISTE" -ForegroundColor Red
    $errorCount++
}

# ============================================================================
# 6. RESUMEN Y RECOMENDACIONES
# ============================================================================
Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║                         RESUMEN                                    ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

if ($errorCount -eq 0 -and $warningCount -eq 0) {
    Write-Host " ¡TODO ESTÁ PERFECTO! " -ForegroundColor Green
    Write-Host ""
    Write-Host "El sistema de themes está correctamente configurado." -ForegroundColor Green
    Write-Host ""
} elseif ($errorCount -eq 0) {
    Write-Host "Sistema funcional con $warningCount advertencia(s)" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "El theme debería funcionar, pero hay mejoras sugeridas arriba." -ForegroundColor Yellow
    Write-Host ""
} else {
    Write-Host " Se encontraron $errorCount error(es) y $warningCount advertencia(s)" -ForegroundColor Red
    Write-Host ""
    Write-Host " PASOS SUGERIDOS:" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "1. Instalar dependencias:" -ForegroundColor White
    Write-Host "   npm install" -ForegroundColor Gray
    Write-Host ""
    Write-Host "2. Compilar assets:" -ForegroundColor White
    Write-Host "   npm run build" -ForegroundColor Gray
    Write-Host ""
    Write-Host "3. Limpiar cachés:" -ForegroundColor White
    Write-Host "   php artisan optimize:clear" -ForegroundColor Gray
    Write-Host ""
    Write-Host "4. Verificar nuevamente:" -ForegroundColor White
    Write-Host "   .\verify-theme.ps1" -ForegroundColor Gray
    Write-Host ""
}

Write-Host "COMANDOS ÚTILES:" -ForegroundColor Cyan
Write-Host ""
Write-Host "   npm run dev          # Desarrollo con hot-reload" -ForegroundColor Gray
Write-Host "   npm run build        # Compilar para producción" -ForegroundColor Gray
Write-Host "   php artisan optimize:clear  # Limpiar cachés" -ForegroundColor Gray
Write-Host ""

# ============================================================================
# 7. OPCIÓN DE AUTO-CORRECCIÓN
# ============================================================================
if ($errorCount -gt 0) {
    Write-Host " ¿Deseas intentar auto-corrección? (s/n): " -NoNewline -ForegroundColor Yellow
    $autofix = Read-Host

    if ($autofix -eq "s" -or $autofix -eq "S") {
        Write-Host ""
        Write-Host "INICIANDO AUTO-CORRECCIÓN..." -ForegroundColor Cyan
        Write-Host ""

        # Instalar dependencias si no existen
        if (-not (Test-Path "node_modules")) {
            Write-Host " Instalando dependencias npm..." -ForegroundColor Yellow
            npm install
        }

        # Compilar assets
        Write-Host "🔨 Compilando assets..." -ForegroundColor Yellow
        npm run build

        # Limpiar cachés
        Write-Host " Limpiando cachés..." -ForegroundColor Yellow
        php artisan optimize:clear

        Write-Host ""
        Write-Host " Auto-corrección completada" -ForegroundColor Green
        Write-Host " Recarga el navegador con Ctrl+Shift+R" -ForegroundColor Cyan
        Write-Host ""
    }
}

Write-Host "════════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
