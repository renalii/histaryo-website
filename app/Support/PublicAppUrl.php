<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * URLs for emails and signed invite links (MAIL_PUBLIC_BASE_URL, else APP_URL).
 */
class PublicAppUrl
{
    public static function base(): string
    {
        return rtrim((string) config('app.public_url', config('app.url')), '/');
    }

    public static function route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        return (string) static::usingPublicRoot(fn () => route($name, $parameters, $absolute));
    }

    public static function temporarySignedRoute(string $name, Carbon|\DateTimeInterface $expiration, array $parameters = []): string
    {
        return (string) static::usingPublicRoot(fn () => URL::temporarySignedRoute($name, $expiration, $parameters));
    }

    public static function signedRoute(string $name, array $parameters = []): string
    {
        return (string) static::usingPublicRoot(fn () => URL::signedRoute($name, $parameters));
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function usingPublicRoot(Closure $callback): mixed
    {
        $localRoot = rtrim((string) config('app.url'), '/');
        $publicBase = static::base();
        $forceHttps = str_starts_with($publicBase, 'https://');

        URL::forceRootUrl($publicBase);
        if ($forceHttps) {
            URL::forceScheme('https');
        }

        try {
            return $callback();
        } finally {
            URL::forceRootUrl($localRoot !== '' ? $localRoot : null);
            if ($forceHttps) {
                URL::forceScheme(null);
            }
        }
    }
}
