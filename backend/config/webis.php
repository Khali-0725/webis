<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Frontend URL
    |--------------------------------------------------------------------------
    |
    | Used to build password-reset and email-verification links that point at
    | the React SPA rather than at a Blade route.
    |
    */

    'frontend_url' => rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/'),

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'default' => 15,
        'max' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    |
    | Every upload is validated against both extension and MIME type, and is
    | written to the private disk. Nothing user-uploaded is ever web-readable
    | by path; files are served through signed, policy-checked routes.
    |
    */

    'uploads' => [
        'disk' => env('WEBIS_UPLOAD_DISK', 'local'),
        'image_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'image_max_kb' => 4096,
        'document_mimes' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
        'document_max_kb' => 8192,
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale defaults
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'currency' => 'PHP',
        'municipality' => 'Tanza',
        'province' => 'Cavite',
        'timezone' => 'Asia/Manila',
    ],

];
