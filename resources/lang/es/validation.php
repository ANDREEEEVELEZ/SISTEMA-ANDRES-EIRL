<?php
return [
    'required' => 'El campo :attribute es obligatorio.',
    'numeric' => 'El campo :attribute debe ser un número.',
    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'max' => [
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe tener más de :max caracteres.',
    ],
    'between' => [
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'string' => 'El campo :attribute debe tener entre :min y :max caracteres.',
    ],
    'digits_between' => 'El campo :attribute debe tener entre :min y :max dígitos.',
    'max_digits' => 'El campo :attribute no debe tener más de :max dígitos.',
    'min_digits' => 'El campo :attribute debe tener al menos :min dígitos.',
    'digits' => 'El campo :attribute debe tener :digits dígitos.',
    'size' => [
        'numeric' => 'El campo :attribute debe ser :size.',
        'string' => 'El campo :attribute debe tener :size caracteres.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "rule.attribute" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'num_doc' => [
            'max' => 'El número de documento no debe tener más de :max dígitos.',
            'min' => 'El número de documento debe tener al menos :min dígitos.',
            'between' => 'El número de documento debe tener entre :min y :max dígitos.',
        ],
        'telefono' => [
            'size' => 'El teléfono debe tener exactamente :size dígitos.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'num_doc' => 'número de documento',
        'telefono' => 'teléfono',
        'tipo_doc' => 'tipo de documento',
        'tipo_cliente' => 'tipo de cliente',
        'nombre_razon' => 'nombre o razón social',
        'fecha_registro' => 'fecha de registro',
        'estado' => 'estado',
        'direccion' => 'dirección',
    ],
];
