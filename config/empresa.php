<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Datos de la Empresa
    |--------------------------------------------------------------------------
    |
    | Configuración de los datos de la empresa que se mostrarán en los
    | comprobantes impresos.
    |
    */

    'nombre' => env('EMPRESA_NOMBRE', 'SNACKS MR CHIPS'),
    'ruc' => env('EMPRESA_RUC', '10036736475'),
    'razon_social' => env('EMPRESA_RAZON_SOCIAL', 'SOBRINO REQUENA DE SIANCAS LEONOR'),
    'nombre_comercial' => env('EMPRESA_NOMBRE_COMERCIAL', 'SNACKS MR CHIPS'),
    'direccion' => env('EMPRESA_DIRECCION', 'AV. JOSE DE LAMA NRO. 1192 A.H. SANCHEZ CERRO'),
    'ubigeo' => env('EMPRESA_UBIGEO', '200601'), // Código UBIGEO INEI - SULLANA
    'departamento' => env('EMPRESA_DEPARTAMENTO', 'PIURA'),
    'provincia' => env('EMPRESA_PROVINCIA', 'SULLANA'),
    'distrito' => env('EMPRESA_DISTRITO', 'SULLANA'),
    'urbanizacion' => env('EMPRESA_URBANIZACION', 'A.H. SANCHEZ CERRO'),
    'telefono' => env('EMPRESA_TELEFONO', '- -'),
   // 'email' => env('EMPRESA_EMAIL', 'ventas@andreseirl.com'),
   // 'web' => env('EMPRESA_WEB', 'www.andreseirl.com'),

    /*
    |--------------------------------------------------------------------------
    | Logo de la Empresa
    |--------------------------------------------------------------------------
    |
    | Ruta relativa al logo de la empresa desde public/
    |
    */
    'logo' => env('EMPRESA_LOGO', 'images/logo.png'),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Impresión
    |--------------------------------------------------------------------------
    |
    | Configuración para la impresión de tickets térmicos
    |
    */
    'ticket' => [
        'ancho_papel' => '80mm', // 58mm o 80mm
        'auto_print' => false, // Auto-imprimir al cargar
        'auto_close' => false, // Auto-cerrar después de imprimir
    ],
];
