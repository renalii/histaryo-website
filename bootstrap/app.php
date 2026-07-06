<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        // ngrok / reverse proxies: honor X-Forwarded-Proto so signed URLs (https) validate.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'curator.auth' => \App\Http\Middleware\EnsureCuratorSession::class,
            'panel.admin' => \App\Http\Middleware\EnsureAdminPanelSession::class,
            'panel.sitemanager' => \App\Http\Middleware\EnsureSiteManagerPanelSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
