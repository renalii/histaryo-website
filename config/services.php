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
    
    'firebase' => [
        'api_key' => env('FIREBASE_API_KEY'),
        // grpc (default) or rest — rest avoids flaky gRPC "Stream removed" on some Windows/dev setups.
        'firestore_transport' => strtolower((string) env('FIRESTORE_TRANSPORT', 'grpc')),
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
