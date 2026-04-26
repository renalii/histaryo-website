<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public base URL for QR “resolve” links
    |--------------------------------------------------------------------------
    |
    | QR codes embed a full URL. 127.0.0.1 / localhost only work on the machine
    | that runs the server — phones need a reachable host (e.g. ngrok HTTPS).
    |
    | Recommended for dev (any network): run `ngrok http 8000`, then
    | `php artisan ngrok:sync` (sets this value only; keep APP_URL as http://127.0.0.1:8000 for local Curators).
    |
    | Leave empty to use Laravel’s route() root (APP_URL) for embedded links.
    |
    */

    'public_base_url' => env('QR_PUBLIC_BASE_URL', ''),

];
