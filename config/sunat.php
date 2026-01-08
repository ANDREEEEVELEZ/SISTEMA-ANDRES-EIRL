<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modo de Operación SUNAT/OSE
    |--------------------------------------------------------------------------
    |
    | Valores disponibles:
    | - 'DEMO' : Nubefact OSE Demo (https://demo-ose.nubefact.com)
    |            ⚠️ Usuario debe ser: RUC+USUARIO (ej: 10036736475MODDATOS)
    | - 'BETA' : SUNAT Facturador Gratuito Beta
    | - 'PROD' : SUNAT Producción
    |
    */
    'mode' => env('SUNAT_MODE', 'DEMO'),

    /*
    |--------------------------------------------------------------------------
    | Credenciales
    |--------------------------------------------------------------------------
    |
    | RUC: RUC de la empresa
    | USER: Para DEMO (Nubefact OSE) debe ser RUC+USUARIO
    |       Para BETA/PROD (SUNAT directo) solo el usuario SOL
    | PASS: Contraseña
    |
    */
    'ruc' => env('SUNAT_RUC', '10036736475'),
    'user' => env('SUNAT_USER', '10036736475MODDATOS'),
    'pass' => env('SUNAT_PASS', 'MODDATOS'),

    /*
    |--------------------------------------------------------------------------
    | Certificado Digital
    |--------------------------------------------------------------------------
    |
    | Ruta al archivo certificate.pem (debe estar en la raíz del proyecto)
    |
    */
    'cert_path' => env('SUNAT_CERT_PATH', base_path('certificate.pem')),
];
