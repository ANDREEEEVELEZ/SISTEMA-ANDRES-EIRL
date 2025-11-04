# Script de Limpieza del Proyecto
# Ejecutar desde PowerShell en la raíz del proyecto

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  LIMPIEZA DE ARCHIVOS INNECESARIOS" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

$removidos = 0
$errores = 0

# ==================================================
# 1. SCRIPTS DE PRUEBA/DEBUG
# ==================================================
Write-Host "[1/5] Eliminando scripts de prueba y debug..." -ForegroundColor Yellow

$scriptsDebug = @(
    "check_asistencias.php",
    "debug_reporte.php",
    "test_reporte.php",
    "verificar_correccion.php",
    "ver_asistencias_completo.php",
    "preview_nuevo_reporte.php",
    "verificar_cambios_clientes.php"
)

foreach ($archivo in $scriptsDebug) {
    if (Test-Path $archivo) {
        try {
            Remove-Item $archivo -Force
            Write-Host "  ✓ Eliminado: $archivo" -ForegroundColor Green
            $removidos++
        } catch {
            Write-Host "  ✗ Error al eliminar: $archivo" -ForegroundColor Red
            $errores++
        }
    } else {
        Write-Host "  - No existe: $archivo" -ForegroundColor Gray
    }
}

# ==================================================
# 2. CARPETA ASISTENCIAS VACÍA EN RAÍZ
# ==================================================
Write-Host ""
Write-Host "[2/5] Eliminando carpeta Asistencias duplicada..." -ForegroundColor Yellow

if (Test-Path "Asistencias") {
    $isEmpty = (Get-ChildItem "Asistencias" -Recurse | Measure-Object).Count -eq 0
    if ($isEmpty) {
        try {
            Remove-Item "Asistencias" -Recurse -Force
            Write-Host "  ✓ Eliminada carpeta vacía: Asistencias/" -ForegroundColor Green
            $removidos++
        } catch {
            Write-Host "  ✗ Error al eliminar: Asistencias/" -ForegroundColor Red
            $errores++
        }
    } else {
        Write-Host "  ⚠ La carpeta Asistencias/ NO está vacía. Revisar manualmente." -ForegroundColor Red
    }
} else {
    Write-Host "  - No existe: Asistencias/" -ForegroundColor Gray
}

# ==================================================
# 3. NODE_MODULES (OPCIONAL)
# ==================================================
Write-Host ""
Write-Host "[3/5] Verificando node_modules..." -ForegroundColor Yellow

if (Test-Path "node_modules") {
    $respuesta = Read-Host "¿Eliminar node_modules? (S/N)"
    if ($respuesta -eq "S" -or $respuesta -eq "s") {
        Write-Host "  Eliminando node_modules (esto puede tardar)..." -ForegroundColor Yellow
        try {
            Remove-Item "node_modules" -Recurse -Force
            Write-Host "  ✓ Eliminado: node_modules/" -ForegroundColor Green
            Write-Host "    (Se regenera con: npm install)" -ForegroundColor Gray
            $removidos++
        } catch {
            Write-Host "  ✗ Error al eliminar: node_modules/" -ForegroundColor Red
            $errores++
        }
    } else {
        Write-Host "  - Omitido: node_modules/" -ForegroundColor Gray
    }
} else {
    Write-Host "  - No existe: node_modules/" -ForegroundColor Gray
}

# ==================================================
# 4. ARCHIVOS DE FACTURACIÓN (OPCIONAL)
# ==================================================
Write-Host ""
Write-Host "[4/5] Verificando archivos de facturación electrónica..." -ForegroundColor Yellow

$archivosFacturacion = @(
    "boleta.php",
    "factura.php",
    "nota-credito.php",
    "comunicacion-baja.php",
    "resumen-diario.php",
    "config.php",
    "certificate.pem",
    "20123456789-01-F001-1.xml",
    "R-20123456789-01-F001-1.zip"
)

Write-Host "  ¿Tu sistema usa facturación electrónica SUNAT?" -ForegroundColor Cyan
$respuesta = Read-Host "  (S = Mantener archivos, N = Eliminar ejemplos)"

if ($respuesta -eq "N" -or $respuesta -eq "n") {
    foreach ($archivo in $archivosFacturacion) {
        if (Test-Path $archivo) {
            try {
                Remove-Item $archivo -Force
                Write-Host "  ✓ Eliminado: $archivo" -ForegroundColor Green
                $removidos++
            } catch {
                Write-Host "  ✗ Error al eliminar: $archivo" -ForegroundColor Red
                $errores++
            }
        }
    }
} else {
    Write-Host "  - Omitidos archivos de facturación" -ForegroundColor Gray
}

# ==================================================
# 5. DOCUMENTACIÓN TEMPORAL
# ==================================================
Write-Host ""
Write-Host "[5/5] Limpiando documentación temporal..." -ForegroundColor Yellow

$docsTemporales = @(
    "CAMBIOS_REPORTE.md",
    "ARCHIVOS_A_ELIMINAR.md"
)

foreach ($archivo in $docsTemporales) {
    if (Test-Path $archivo) {
        $respuesta = Read-Host "¿Eliminar $archivo? (S/N)"
        if ($respuesta -eq "S" -or $respuesta -eq "s") {
            try {
                Remove-Item $archivo -Force
                Write-Host "  ✓ Eliminado: $archivo" -ForegroundColor Green
                $removidos++
            } catch {
                Write-Host "  ✗ Error al eliminar: $archivo" -ForegroundColor Red
                $errores++
            }
        }
    }
}

# ==================================================
# RESUMEN
# ==================================================
Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  RESUMEN DE LIMPIEZA" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  Archivos eliminados: $removidos" -ForegroundColor Green
Write-Host "  Errores: $errores" -ForegroundColor $(if ($errores -gt 0) { "Red" } else { "Gray" })
Write-Host ""
Write-Host "✅ Limpieza completada." -ForegroundColor Green
Write-Host ""

# Calcular espacio aproximado liberado (si eliminó node_modules)
if (-not (Test-Path "node_modules")) {
    Write-Host "💾 Espacio estimado liberado: ~200-500 MB" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "Presiona Enter para cerrar..." -ForegroundColor Gray
Read-Host
