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

    'nombre' => env('EMPRESA_NOMBRE', 'ANDRÉS E.I.R.L.'),
    'ruc' => env('EMPRESA_RUC', '20123456789'),
    'direccion' => env('EMPRESA_DIRECCION', 'Av. Principal 123, Lima - Perú'),
    'telefono' => env('EMPRESA_TELEFONO', '(01) 234-5678'),
    'email' => env('EMPRESA_EMAIL', 'ventas@andreseirl.com'),
    'web' => env('EMPRESA_WEB', 'www.andreseirl.com'),
    
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
