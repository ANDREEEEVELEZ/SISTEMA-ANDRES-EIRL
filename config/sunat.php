<?php

return [

    'mode' => env('SUNAT_MODE', 'BETA'),


    'ruc' => env('SUNAT_RUC', '20000000001'),
    'user' => env('SUNAT_USER', 'MODDATOS'),
    'pass' => env('SUNAT_PASS', 'moddatos'),

    'cert_path' => env('SUNAT_CERT_PATH', base_path('certificate.pem')),
];
