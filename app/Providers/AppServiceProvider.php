<?php

namespace App\Providers;

use App\Services\FirebaseService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FirebaseService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $root = rtrim((string) config('app.url'), '/');
        if ($root !== '') {
            URL::forceRootUrl($root);
        }
    }
}
