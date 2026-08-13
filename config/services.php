<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */
    

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'mapbox' => [
        'token' => env('MAPBOX_TOKEN'),
    ],

    'qr' => [
        'function_url' => env('QR_FUNCTION_URL'),
        'function_key' => env('QR_FUNCTION_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
        'landmark_folder' => env('CLOUDINARY_LANDMARK_FOLDER', 'histaryo/landmarks'),
    ],

    'frontend' => [
        'url' => env(
            'FRONTEND_URL',
            env('MAIL_PUBLIC_BASE_URL', env('APP_URL'))
        ),
    ],
    
    'firebase' => [
        'api_key' => env('FIREBASE_API_KEY'),
        'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),
        // Bound Google API waits so connectivity failures do not exhaust PHP's execution limit.
        'connect_timeout' => (float) env('FIREBASE_CONNECT_TIMEOUT', 10),
        'request_timeout' => (float) env('FIREBASE_REQUEST_TIMEOUT', 20),
        'firestore_transport' => env('FIRESTORE_TRANSPORT', 'grpc'),
        'tip_query_timeout' => (float) env('FIREBASE_TIP_QUERY_TIMEOUT', 3),
        'dashboard_query_timeout' => (float) env('FIREBASE_DASHBOARD_QUERY_TIMEOUT', 3),
        // Optional comma-separated extra Firestore collection ids for crowd tips (merged with app defaults).
        'firestore_tip_collection_names' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('FIRESTORE_TIP_COLLECTIONS', ''))
        ))),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
