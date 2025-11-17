![Logo](public/images/logo.png)

# MANUAL TÉCNICO DEL SISTEMA WEB

**SISTEMA DE GESTIÓN COMERCIAL**
**CHIFLES ANDRES E.I.R.L.**

---

## INFORMACIÓN GENERAL DEL SISTEMA

### Propósito del Documento

Este manual técnico tiene como propósito brindar información detallada sobre la arquitectura, instalación, configuración y mantenimiento del Sistema de Gestión Comercial desarrollado para Chifles Andres E.I.R.L. Está dirigido a desarrolladores, administradores de sistemas y personal técnico encargado del mantenimiento y soporte del sistema.

### Audiencia Objetivo

- Desarrolladores y programadores
- Administradores de sistemas
- Personal de soporte técnico
- Jefes de TI y supervisores técnicos

### Alcance del Sistema Web

El sistema web abarca la gestión integral de operaciones comerciales, incluyendo:

- Gestión de ventas y facturación electrónica
- Control de inventario y productos
- Administración de clientes y proveedores
- Gestión de personal y asistencias
- Reportes y análisis de datos
- Integración con SUNAT

---

## ÍNDICE DE CONTENIDOS

1. [INTRODUCCIÓN](#1-introducción)
   - 1.1. [Propósito del Documento](#11-propósito-del-documento)
   - 1.2. [Audiencia Objetivo](#12-audiencia-objetivo)
   - 1.3. [Alcance del Sistema Web](#13-alcance-del-sistema-web)

2. [REQUERIMIENTOS DEL SISTEMA](#2-requerimientos-del-sistema)
   - 2.1. [Requerimientos Funcionales](#21-requerimientos-funcionales)
   - 2.2. [Requerimientos No Funcionales](#22-requerimientos-no-funcionales)

3. [ARQUITECTURA DEL SISTEMA](#3-arquitectura-del-sistema)
   - 3.1. [Descripción de la arquitectura empleada](#31-descripción-de-la-arquitectura-empleada)
   - 3.2. [Patrón utilizado](#32-patrón-utilizado)
   - 3.3. [Tecnologías Utilizadas](#33-tecnologías-utilizadas)
   - 3.4. [Diagrama de Arquitectura](#34-diagrama-de-arquitectura)

4. [ESTRUCTURA DEL PROYECTO](#4-estructura-del-proyecto)
   - 4.1. [Directorios Principales](#41-directorios-principales)
   - 4.2. [Archivos de Configuración Principales](#42-archivos-de-configuración-principales)

5. [MÓDULOS DEL SISTEMA](#5-módulos-del-sistema)
   - 5.1. [Módulo de Ventas](#51-módulo-de-ventas)
   - 5.2. [Módulo de Comprobantes Electrónicos](#52-módulo-de-comprobantes-electrónicos)
   - 5.3. [Módulo de Caja](#53-módulo-de-caja)
   - 5.4. [Módulo de Inventario](#54-módulo-de-inventario)
   - 5.5. [Módulo de Recursos Humanos](#55-módulo-de-recursos-humanos)

6. [BASE DE DATOS](#6-base-de-datos)
   - 6.1. [Diseño de la Base de Datos](#61-diseño-de-la-base-de-datos)
   - 6.2. [Migraciones](#62-migraciones)

7. [PANEL DE ADMINISTRACIÓN FILAMENT](#7-panel-de-administración-filament)
   - 7.1. [Configuración del Panel](#71-configuración-del-panel)
   - 7.2. [Recursos (Resources)](#72-recursos-resources)
   - 7.3. [Widgets del Dashboard](#73-widgets-del-dashboard)

8. [INTEGRACIÓN CON SUNAT](#8-integración-con-sunat)
   - 8.1. [Configuración de SUNAT](#81-configuración-de-sunat)
   - 8.2. [Servicio SUNAT](#82-servicio-sunat)
   - 8.3. [Tipos de Comprobantes Soportados](#83-tipos-de-comprobantes-soportados)

9. [SEGURIDAD DEL SISTEMA](#9-seguridad-del-sistema)
   - 9.1. [Autenticación y Autorización](#91-autenticación-y-autorización)
   - 9.2. [Políticas de Seguridad](#92-políticas-de-seguridad)
   - 9.3. [Validaciones](#93-validaciones)

10. [TESTING Y PRUEBAS](#10-testing-y-pruebas)
    - 10.1. [Estructura de Tests](#101-estructura-de-tests)
    - 10.2. [Comandos de Testing](#102-comandos-de-testing)
    - 10.3. [Factories](#103-factories)

11. [COMANDOS ARTISAN PERSONALIZADOS](#11-comandos-artisan-personalizados)
    - 11.1. [Comandos Disponibles](#111-comandos-disponibles)
    - 11.2. [Implementación](#112-implementación)

12. [CONFIGURACIÓN DEL ENTORNO](#12-configuración-del-entorno)
    - 12.1. [Variables de Entorno (.env)](#121-variables-de-entorno-env)
    - 12.2. [Instalación](#122-instalación)

13. [DESPLIEGUE (DEPLOYMENT)](#13-despliegue-deployment)
    - 13.1. [Servidor de Producción](#131-servidor-de-producción)
    - 13.2. [Configuración Apache](#132-configuración-apache)
    - 13.3. [Optimizaciones de Producción](#133-optimizaciones-de-producción)

14. [MANTENIMIENTO DEL SISTEMA](#14-mantenimiento-del-sistema)
    - 14.1. [Logs del Sistema](#141-logs-del-sistema)
    - 14.2. [Backup](#142-backup)
    - 14.3. [Monitoreo](#143-monitoreo)

15. [SOLUCIÓN DE PROBLEMAS COMUNES](#15-solución-de-problemas-comunes)
    - 15.1. [Errores de SUNAT](#151-errores-de-sunat)
    - 15.2. [Errores de Base de Datos](#152-errores-de-base-de-datos)
    - 15.3. [Errores de Permisos](#153-errores-de-permisos)

16. [CONTACTO Y SOPORTE TÉCNICO](#16-contacto-y-soporte-técnico)
    - 16.1. [Niveles de Soporte](#161-niveles-de-soporte)
    - 16.2. [Actualizaciones](#162-actualizaciones)

17. [ANEXOS](#17-anexos)
    - 17.1. [Glosario](#171-glosario)
    - 17.2. [Referencias](#172-referencias)

---

### Información del Sistema

| **Campo** | **Detalle** |
|-----------|-------------|
| **Nombre del Sistema** | Sistema de Gestión Comercial para Chifles Andres E.I.R.L. |
| **Versión** | 1.0 |
| **Fecha de Desarrollo** | Noviembre 2025 |
| **Framework Principal** | Laravel 12 + Filament 4 |
| **Empresa** | CHIFLES ANDRES E.I.R.L. |
| **RUC** | 20609709406 |
| **Desarrollador** | Andrés Vélez |
| **URL del Repositorio** | https://github.com/ANDREEEEVELEZ/SISTEMA-ANDRES-EIRL |

---

# 1. INTRODUCCIÓN

## 1.1. PROPÓSITO DEL DOCUMENTO

Este manual técnico proporciona una guía completa para desarrolladores, administradores de sistemas y personal técnico responsable del mantenimiento del Sistema de Gestión Comercial de Chifles Andres E.I.R.L.

El documento incluye:
- Descripción de la arquitectura del sistema
- Procedimientos de instalación y configuración
- Guías de mantenimiento y resolución de problemas
- Documentación técnica de módulos y componentes

## 1.2. AUDIENCIA OBJETIVO

- **Desarrolladores**: Para entender la estructura del código y realizar modificaciones
- **Administradores de Sistema**: Para instalación, configuración y mantenimiento
- **Personal de Soporte**: Para resolución de problemas y asistencia técnica
- **Equipo de TI**: Para supervisión y gestión del sistema

## 1.3. ALCANCE DEL SISTEMA WEB

El sistema web desarrollado abarca los siguientes módulos principales:

- **Módulo de Gestión de Ventas**: Registro y control de ventas
- **Módulo de Facturación Electrónica**: Integración con SUNAT
- **Módulo de Gestión de Inventario**: Control de productos y stock
- **Módulo de Gestión de Clientes**: Administración de base de clientes
- **Módulo de Gestión de Personal**: Control de empleados y asistencias
- **Módulo de Reportes**: Generación de informes y análisis
- **Módulo de Configuración**: Parametrización del sistema

---

# 2. REQUERIMIENTOS DEL SISTEMA

## 2.1. REQUERIMIENTOS FUNCIONALES

### RF01 - Gestión de Ventas

**RF01.01**: El sistema debe permitir registrar ventas con diferentes tipos de comprobante (Ticket, Boleta, Factura)
**RF01.02**: El sistema debe calcular automáticamente IGV, descuentos y totales
**RF01.03**: El sistema debe soportar múltiples métodos de pago (efectivo, tarjeta, transferencia, Yape, Plin)
**RF01.04**: El sistema debe permitir la anulación de ventas con justificación

### RF02 - Gestión de Clientes

**RF02.01**: El sistema debe permitir registrar clientes con DNI y RUC
**RF02.02**: El sistema debe validar documentos de identidad con SUNAT/RENIEC
**RF02.03**: El sistema debe mantener historial de compras por cliente
**RF02.04**: El sistema debe generar reportes de clientes

### RF03 - Gestión de Inventario

**RF03.01**: El sistema debe controlar stock de productos en tiempo real
**RF03.02**: El sistema debe alertar sobre stock mínimo
**RF03.03**: El sistema debe categorizar productos
**RF03.04**: El sistema debe generar reportes de inventario

### RF04 - Facturación Electrónica

**RF04.01**: El sistema debe generar XML según estándares UBL 2.1
**RF04.02**: El sistema debe enviar comprobantes a SUNAT automáticamente
**RF04.03**: El sistema debe procesar respuestas CDR de SUNAT
**RF04.04**: El sistema debe generar notas de crédito y débito
**RF04.05**: El sistema debe enviar resúmenes diarios y comunicaciones de baja

### RF05 - Gestión de Personal

**RF05.01**: El sistema debe registrar empleados con datos completos
**RF05.02**: El sistema debe controlar asistencias con reconocimiento facial
**RF05.03**: El sistema debe generar reportes de asistencia
**RF05.04**: El sistema debe calcular horas trabajadas

## 2.2. REQUERIMIENTOS NO FUNCIONALES

### RNF01 - Rendimiento

**RNF01.01**: El sistema debe procesar transacciones en menos de 3 segundos
**RNF01.02**: El sistema debe soportar al menos 50 usuarios concurrentes
**RNF01.03**: El sistema debe tener tiempo de respuesta menor a 2 segundos para consultas

### RNF02 - Seguridad

**RNF02.01**: El sistema debe implementar autenticación de usuarios
**RNF02.02**: El sistema debe usar conexiones HTTPS
**RNF02.03**: El sistema debe registrar logs de todas las transacciones
**RNF02.04**: El sistema debe implementar roles y permisos

### RNF03 - Compatibilidad

**RNF03.01**: El sistema debe ser compatible con navegadores modernos
**RNF03.02**: El sistema debe funcionar en dispositivos móviles
**RNF03.03**: El sistema debe integrarse con impresoras térmicas

### RNF04 - Disponibilidad

**RNF04.01**: El sistema debe estar disponible 99.5% del tiempo
**RNF04.02**: El sistema debe tener respaldo automático de datos

### RNF05 - Mantenibilidad

**RNF05.01**: El sistema debe permitir actualizaciones sin afectar datos existentes
**RNF05.02**: El sistema debe generar logs detallados para debugging

---

# 3. ARQUITECTURA DEL SISTEMA

## 3.1. Descripción de la arquitectura empleada

El sistema utiliza una arquitectura MVC (Model-View-Controller) basada en Laravel, con las siguientes capas:

- **Capa de Presentación**: Filament Admin Panel para interfaz de usuario
- **Capa de Lógica de Negocio**: Controllers y Services de Laravel
- **Capa de Datos**: Eloquent ORM con modelos de base de datos
- **Capa de Integración**: APIs y servicios externos (SUNAT, RENIEC)

## 3.2. Patrón utilizado

El sistema implementa el patrón **MVC (Model-View-Controller)** de Laravel con arquitectura en capas:

```
┌─────────────────────────────────────────┐
│         CAPA DE PRESENTACIÓN           │
│         (Filament Admin Panel)         │
├─────────────────────────────────────────┤
│         CAPA DE CONTROLADORES          │
│    (HTTP Controllers + Livewire)       │
├─────────────────────────────────────────┤
│        CAPA DE LÓGICA DE NEGOCIO       │
│        (Services + Repositories)      │
├─────────────────────────────────────────┤
│          CAPA DE DATOS                 │
│         (Eloquent Models)              │
├─────────────────────────────────────────┤
│        CAPA DE PERSISTENCIA            │
│          (Base de Datos MySQL)         │
└─────────────────────────────────────────┘
```

## 3.3. Tecnologías Utilizadas

| **Componente** | **Tecnología** | **Versión** | **Propósito** |
|----------------|----------------|-------------|---------------|
| **Backend Framework** | Laravel | ^12.0 | Framework principal |
| **Frontend Admin** | Filament | 4.0 | Panel de administración |
| **Base de Datos** | MySQL | >= 8.0 | Almacenamiento de datos |
| **Servidor Web** | Apache/Nginx | - | Servidor HTTP |
| **PHP** | PHP | ^8.2 | Lenguaje de programación |
| **Gestor de Dependencias** | Composer | - | Gestión de paquetes PHP |
| **Build Assets** | Vite | - | Compilación de assets |
| **Autenticación** | Laravel Breeze | - | Sistema de login |
| **Permisos** | Spatie Laravel Permission | ^6.21 | Roles y permisos |
| **PDF Generation** | DomPDF | ^3.1 | Generación de PDFs |
| **SUNAT Integration** | Greenter Lite | ^5.1 | Facturación electrónica |

## 3.4. Diagrama de Arquitectura

La arquitectura del sistema se basa en los siguientes componentes principales:

### Módulos del Sistema:
- **Módulo de Ventas**: Gestión integral de ventas y transacciones
- **Módulo de Facturación**: Generación y envío de comprobantes electrónicos
- **Módulo de Inventario**: Control de stock y productos
- **Módulo de Clientes**: Administración de base de clientes
- **Módulo de Recursos Humanos**: Gestión de empleados y asistencias
- **Módulo de Reportes**: Generación de informes y análisis
- **Módulo de Configuración**: Parametrización del sistema

---

# 4. ESTRUCTURA DEL PROYECTO

## 4.1. Directorios Principales

```
SISTEMA-ANDRES EIRL/
│
├── app/                          # Código de la aplicación
│   ├── Console/Commands/         # Comandos artisan personalizados
│   ├── Enums/                   # Enumeraciones
│   ├── Filament/                # Configuración de Filament
│   │   ├── Actions/             # Acciones personalizadas
│   │   ├── Forms/               # Esquemas de formularios
│   │   ├── Pages/               # Páginas personalizadas
│   │   ├── Resources/           # Recursos CRUD
│   │   └── Widgets/             # Widgets del dashboard
│   ├── Http/                    # Capa HTTP
│   │   ├── Controllers/         # Controladores
│   │   ├── Middleware/          # Middleware personalizado
│   │   ├── Requests/            # Form Requests
│   │   └── Resources/           # API Resources
│   ├── Livewire/               # Componentes Livewire
│   ├── Models/                 # Modelos Eloquent
│   ├── Policies/               # Políticas de autorización
│   ├── Providers/              # Service Providers
│   └── Services/               # Servicios de negocio
│
├── config/                     # Archivos de configuración
│   ├── app.php                # Configuración general
│   ├── database.php           # Configuración de BD
│   ├── empresa.php            # Datos de la empresa
│   └── sunat.php              # Configuración SUNAT
│
├── database/                  # Base de datos
│   ├── factories/             # Factories para testing
│   ├── migrations/            # Migraciones
│   ├── seeders/              # Seeders
│   └── sql/                  # Scripts SQL adicionales
│
├── resources/                # Recursos frontend
│   ├── css/                  # Estilos CSS
│   ├── js/                   # JavaScript
│   ├── lang/                 # Archivos de idioma
│   └── views/                # Vistas Blade
│
├── routes/                   # Definición de rutas
│   ├── web.php              # Rutas web
│   └── console.php          # Rutas de consola
│
├── storage/                 # Almacenamiento
│   ├── app/                # Archivos de la aplicación
│   ├── framework/          # Archivos del framework
│   └── logs/               # Logs del sistema
│
├── tests/                  # Tests automatizados
│   ├── Feature/            # Tests de características
│   └── Unit/               # Tests unitarios
│
└── vendor/                 # Dependencias de Composer
```

## 4.2. Archivos de Configuración Principales

- **`composer.json`**: Dependencias PHP y scripts
- **`package.json`**: Dependencias Node.js y scripts de build
- **`vite.config.js`**: Configuración de Vite para assets
- **`.env`**: Variables de entorno (no incluido en el repositorio)
- **`config/empresa.php`**: Configuración específica de la empresa

---

# 5. MÓDULOS DEL SISTEMA

## 5.1. Módulo de Ventas

**Funcionalidades principales:**
- Crear ventas con múltiples tipos de comprobante (Ticket, Boleta, Factura)
- Gestión de clientes y productos
- Cálculo automático de IGV y descuentos
- Múltiples métodos de pago (Efectivo, Tarjeta, Yape, Plin, Transferencia)
- Integración con SUNAT para comprobantes electrónicos

**Modelos principales:**
- `Venta`: Modelo principal de ventas
- `DetalleVenta`: Detalle de productos por venta
- `Cliente`: Gestión de clientes
- `Producto`: Catálogo de productos

**Archivos clave:**
```
app/Models/Venta.php
app/Models/DetalleVenta.php
app/Filament/Resources/Ventas/VentaResource.php
app/Filament/Resources/Ventas/Schemas/VentaForm.php
```

## 5.2. Módulo de Comprobantes Electrónicos

**Funcionalidades principales:**
- Generación de XML para SUNAT
- Envío automático y manual a SUNAT
- Gestión de series y correlativos
- Emisión de notas de crédito y débito
- Comunicación de bajas y resúmenes diarios

**Modelos principales:**
- `Comprobante`: Gestión de comprobantes
- `SerieComprobante`: Series y correlativos
- `ComprobanteRelacion`: Relaciones entre comprobantes

**Servicios:**
- `SunatService`: Comunicación con SUNAT usando Greenter

## 5.3. Módulo de Caja

**Funcionalidades principales:**
- Apertura y cierre de caja
- Arqueos de efectivo
- Registro de ingresos y egresos
- Control de movimientos de caja

**Modelos principales:**
- `Caja`: Definición de cajas
- `MovimientoCaja`: Movimientos de efectivo
- `Arqueo`: Arqueos de caja

## 5.4. Módulo de Inventario

**Funcionalidades principales:**
- Gestión de productos y categorías
- Control de stock
- Precios y promociones
- Reportes de inventario

**Modelos principales:**
- `Producto`: Catálogo de productos
- `Categoria`: Categorías de productos

## 5.5. Módulo de Recursos Humanos

**Funcionalidades principales:**
- Gestión de empleados
- Control de asistencias
- Reconocimiento facial para asistencia
- Reportes de asistencia

**Modelos principales:**
- `Empleado`: Datos de empleados
- `Asistencia`: Registro de asistencias

---

# 6. BASE DE DATOS

## 6.1. Diseño de la Base de Datos

El sistema utiliza MySQL como gestor de base de datos con las siguientes tablas principales:

#### Tablas de Ventas
```sql
ventas (
    id, user_id, cliente_id, caja_id,
    subtotal_venta, igv, descuento_total, total_venta,
    fecha_venta, hora_venta, estado_venta,
    metodo_pago, cod_operacion, nombre_cliente_temporal
)

detalle_ventas (
    id, venta_id, producto_id,
    cantidad_venta, precio_unitario, subtotal, descuento_unitario
)
```

#### Tablas de Comprobantes
```sql
comprobantes (
    id, venta_id, serie_comprobante_id,
    tipo, codigo_tipo_nota, serie, correlativo,
    fecha_emision, sub_total, igv, total, estado,
    hash_sunat, codigo_sunat, xml_firmado,
    ruta_xml, ruta_cdr, fecha_envio_sunat
)

series_comprobantes (
    id, caja_id, tipo_comprobante, serie,
    ultimo_correlativo, activa
)
```

#### Tablas Maestras
```sql
clientes (
    id, tipo_documento, numero_documento,
    nombre_razon, direccion, telefono, email
)

productos (
    id, categoria_id, codigo, nombre_producto,
    precio_venta, stock_actual, stock_minimo
)

empleados (
    id, nombre, apellidos, dni, telefono,
    cargo, sueldo, fecha_ingreso, estado
)
```

## 6.2. Migraciones

Las migraciones se encuentran en `database/migrations/` y siguen la convención:
- `YYYY_MM_DD_HHMMSS_create_tabla_table.php`

Comandos útiles:
```bash
php artisan migrate                    # Ejecutar migraciones
php artisan migrate:rollback          # Revertir última migración
php artisan migrate:fresh --seed      # Recrear BD con datos de prueba
```

---

# 7. PANEL DE ADMINISTRACIÓN FILAMENT

## 7.1. Configuración del Panel

Filament está configurado como panel de administración principal:

```php
// config/filament.php (generado automáticamente)
```

## 7.2. Recursos (Resources)

Cada módulo tiene su Resource correspondiente:

```
app/Filament/Resources/
├── Ventas/
│   ├── VentaResource.php           # Recurso principal
│   ├── Pages/                      # Páginas personalizadas
│   ├── Schemas/                    # Esquemas de formularios
│   └── Widgets/                    # Widgets específicos
├── Inventario/
├── Clientes/
└── Empleados/
```

## 7.3. Widgets del Dashboard

```php
// app/Filament/Widgets/
EstadisticasPrincipalesWidget.php   # Estadísticas generales
VentasDelDiaWidget.php              # Ventas del día
GraficoVentasWidget.php             # Gráficos de ventas
```

---

# 8. INTEGRACIÓN CON SUNAT

## 8.1. Configuración de SUNAT

La integración con SUNAT se realiza mediante la librería **Greenter**:

```json
// composer.json
"greenter/lite": "^5.1"
```

Configuración en `config/sunat.php`:
```php
return [
    'mode' => env('SUNAT_MODE', 'BETA'),    // BETA o PRODUCCION
    'ruc' => env('SUNAT_RUC'),
    'user' => env('SUNAT_USER'),
    'pass' => env('SUNAT_PASS'),
    'cert_path' => env('SUNAT_CERT_PATH'),
];
```

## 8.2. Servicio SUNAT

`app/Services/SunatService.php` maneja:
- Generación de XML
- Firmado digital
- Envío a SUNAT
- Procesamiento de respuestas CDR

## 8.3. Tipos de Comprobantes Soportados

| Tipo | Código | Descripción |
|------|--------|-------------|
| Factura | 01 | Comprobante para empresas |
| Boleta | 03 | Comprobante para personas |
| Nota de Crédito | 07 | Anulaciones y devoluciones |
| Nota de Débito | 08 | Incrementos |
| Ticket | T001 | Comprobante interno |

---

# 9. SEGURIDAD DEL SISTEMA

## 9.1. Autenticación y Autorización

- Sistema basado en **Laravel Breeze** con **Filament Shield**
- Roles y permisos con **Spatie Laravel Permission**
- Middleware personalizado para protección de rutas

## 9.2. Políticas de Seguridad

```php
// app/Policies/
VentaPolicy.php         # Políticas para ventas
ClientePolicy.php       # Políticas para clientes
EmpleadoPolicy.php      # Políticas para empleados
```

## 9.3. Validaciones

- Form Requests para validación de datos
- Reglas de validación en modelos Eloquent
- Validaciones en tiempo real en formularios Filament

---

# 10. TESTING Y PRUEBAS

## 10.1. Estructura de Tests

```
tests/
├── Feature/            # Tests de integración
│   ├── VentaTest.php
│   ├── ClienteTest.php
│   └── ComprobanteTest.php
└── Unit/               # Tests unitarios
    ├── VentaTest.php
    └── SunatServiceTest.php
```

## 10.2. Comandos de Testing

```bash
php artisan test                    # Ejecutar todos los tests
php artisan test --filter VentaTest # Test específico
php artisan test --coverage        # Con cobertura
```

## 10.3. Factories

Factories para generar datos de prueba:
```php
// database/factories/
VentaFactory.php
ClienteFactory.php
ProductoFactory.php
```

---

# 11. COMANDOS ARTISAN PERSONALIZADOS

## 11.1. Comandos Disponibles

```bash
# Prueba de envío a SUNAT
php artisan sunat:test {comprobante_id}

# Reenvío masivo de comprobantes
php artisan sunat:reenviar

# Limpieza de archivos temporales
php artisan sistema:limpiar

# Generar reportes programados
php artisan reportes:generar
```

## 11.2. Implementación

```php
// app/Console/Commands/
PruebaEnvioSunat.php    # Comando de prueba SUNAT
ReenviarComprobantes.php # Reenvío masivo
```

---

# 12. CONFIGURACIÓN DEL ENTORNO

### 10.1 Variables de Entorno (.env)

```env
# Aplicación
APP_NAME="Chifles Andres EIRL"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost

# Base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chifles_andres
DB_USERNAME=root
DB_PASSWORD=

# SUNAT
SUNAT_MODE=BETA
SUNAT_RUC=20609709406
SUNAT_USER=MODDATOS
SUNAT_PASS=moddatos
SUNAT_CERT_PATH=storage/certificates/certificate.pem

# Empresa
EMPRESA_NOMBRE="CHIFLES ANDRES E.I.R.L."
EMPRESA_RUC=20609709406
EMPRESA_DIRECCION="AV. RAMON CASTILLA NRO 123 CERCADO"
EMPRESA_UBIGEO=200101
```

### 10.2 Instalación

```bash
# 1. Clonar repositorio
git clone https://github.com/ANDREEEEVELEZ/SISTEMA-ANDRES-EIRL.git

# 2. Instalar dependencias
composer install
npm install

# 3. Configurar entorno
cp .env.example .env
php artisan key:generate

# 4. Configurar base de datos
php artisan migrate:fresh --seed

# 5. Generar assets
npm run build

# 6. Crear usuario admin
php artisan make:filament-user

# 7. Instalar Filament Shield
php artisan shield:install
```

---

# 13. DESPLIEGUE (DEPLOYMENT)

### 11.1 Servidor de Producción

**Requisitos mínimos:**
- PHP 8.2+
- MySQL 8.0+
- Apache/Nginx
- SSL Certificate
- 2GB RAM mínimo
- 10GB espacio en disco

### 11.2 Configuración Apache

```apache
<VirtualHost *:443>
    ServerName tudominio.com
    DocumentRoot /var/www/sistema-andres/public
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    
    <Directory /var/www/sistema-andres/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 11.3 Optimizaciones de Producción

```bash
# Optimizar autoloader
composer install --optimize-autoloader --no-dev

# Cachear configuración
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimizar aplicación
php artisan optimize

# Configurar supervisor para queues
sudo supervisorctl start laravel-worker:*
```

---

# 14. MANTENIMIENTO DEL SISTEMA

### 12.1 Logs del Sistema

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Limpiar logs
php artisan log:clear

# Rotar logs
logrotate -d /etc/logrotate.d/laravel
```

### 12.2 Backup

```bash
# Backup de base de datos
mysqldump -u usuario -p chifles_andres > backup_$(date +%Y%m%d).sql

# Backup de archivos
tar -czf backup_files_$(date +%Y%m%d).tar.gz storage/ public/uploads/

# Script automatizado
php artisan backup:run
```

### 12.3 Monitoreo

- **Logs de aplicación**: `storage/logs/`
- **Logs de SUNAT**: `storage/logs/sunat/`
- **Métricas de rendimiento**: Laravel Telescope (opcional)
- **Monitoreo de errores**: Sentry (opcional)

---

# 15. SOLUCIÓN DE PROBLEMAS COMUNES

### 13.1 Errores de SUNAT

**Error: "El RUC no está autorizado"**
```bash
# Verificar configuración
php artisan config:clear
# Revisar archivo .env y config/sunat.php
```

**Error: "Certificado inválido"**
```bash
# Verificar ruta del certificado
ls -la storage/certificates/
# Generar nuevo certificado si es necesario
```

### 13.2 Errores de Base de Datos

**Error: "Connection refused"**
```bash
# Verificar servicio MySQL
sudo systemctl status mysql
# Reiniciar si es necesario
sudo systemctl restart mysql
```

**Error: "Table doesn't exist"**
```bash
# Ejecutar migraciones
php artisan migrate
# O recrear base de datos
php artisan migrate:fresh --seed
```

### 13.3 Errores de Permisos

**Error: "Permission denied"**
```bash
# Configurar permisos correctos
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/
sudo chmod -R 775 bootstrap/cache/
```

---

# 16. CONTACTO Y SOPORTE TÉCNICO

**Desarrollador:** Andrés Vélez  
**Email:** soporte@andreseirl.com  
**Repositorio:** https://github.com/ANDREEEEVELEZ/SISTEMA-ANDRES-EIRL  
**Documentación:** Este manual técnico  

### 14.1 Niveles de Soporte

1. **Nivel 1**: Problemas básicos de usuario
2. **Nivel 2**: Configuración y errores técnicos
3. **Nivel 3**: Desarrollo y modificaciones del sistema

### 14.2 Actualizaciones

- **Menores** (bug fixes): Cada 2 semanas
- **Mayores** (nuevas funcionalidades): Cada 3 meses
- **Críticas** (seguridad): Inmediatas

---

# 17. ANEXOS

### 15.1 Glosario

- **CDR**: Constancia de Recepción (respuesta de SUNAT)
- **OSE**: Operador de Servicios Electrónicos
- **UBL**: Universal Business Language
- **XML**: eXtensible Markup Language
- **CRUD**: Create, Read, Update, Delete

### 15.2 Referencias

- [Documentación Laravel](https://laravel.com/docs)
- [Documentación Filament](https://filamentphp.com/docs)
- [Guía SUNAT Facturación Electrónica](https://cpe.sunat.gob.pe/)
- [Documentación Greenter](https://greenter.dev/)

---

**Fecha de última actualización:** 16 de noviembre de 2025  
**Versión del documento:** 1.0  

---

*Este manual técnico debe mantenerse actualizado con cada versión del sistema. Para sugerencias o correcciones, contactar al equipo de desarrollo.*