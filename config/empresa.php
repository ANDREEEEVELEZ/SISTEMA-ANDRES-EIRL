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

    'nombre' => env('EMPRESA_NOMBRE', 'CHIFLES ANDRES E.I.R.L.'),
    'ruc' => env('EMPRESA_RUC', '20609709406'),
    'razon_social' => env('EMPRESA_RAZON_SOCIAL', 'CHIFLES ANDRES E.I.R.L.'),
    'nombre_comercial' => env('EMPRESA_NOMBRE_COMERCIAL', 'CHIFLES ANDRES'),
    'direccion' => env('EMPRESA_DIRECCION', 'AV. RAMON CASTILLA NRO 123 CERCADO'),
    'ubigeo' => env('EMPRESA_UBIGEO', '200101'), // Código UBIGEO INEI
    'departamento' => env('EMPRESA_DEPARTAMENTO', 'PIURA'),
    'provincia' => env('EMPRESA_PROVINCIA', 'PIURA'),
    'distrito' => env('EMPRESA_DISTRITO', 'PIURA'),
    'urbanizacion' => env('EMPRESA_URBANIZACION', '-'),
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
