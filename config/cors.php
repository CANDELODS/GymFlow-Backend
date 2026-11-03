<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    // Rutas que podrán recibir solicitudes desde el frontend
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'login',
        'logout',
    ],

    // Permitimos los métodos HTTP necesarios
    'allowed_methods' => ['*'],

    // Permitimos solicitudes únicamente desde nuestro frontend
    'allowed_origins' => [
        'http://gymflow.test:8080',
    ],

    'allowed_origins_patterns' => [],

    // Permitimos los encabezados necesarios para las solicitudes
    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Necesario para permitir el envío de cookies de sesión
    'supports_credentials' => true,

];