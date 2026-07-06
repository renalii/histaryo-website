<?php

namespace App\Support;

final class QrResolveUrl
{
    public static function forCode(string $code): string
    {
        $encoded = rawurlencode($code);
        $base = config('qr.public_base_url');
        if (is_string($base) && $base !== '') {
            return rtrim($base, '/').'/resolve/'.$encoded;
        }

        $app = rtrim((string) config('app.url'), '/');
        if ($app !== '' && ! self::isLoopbackHost(parse_url($app, PHP_URL_HOST))) {
            return $app.'/resolve/'.$encoded;
        }

        return route('qr.resolve', ['code' => $code], absolute: true);
    }

    public static function usesLoopbackHost(): bool
    {
        return self::isLoopbackHost(parse_url(self::forCode('x'), PHP_URL_HOST));
    }

    private static function isLoopbackHost(mixed $host): bool
    {
        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = strtolower($host);

        return $host === '127.0.0.1' || $host === 'localhost' || str_starts_with($host, '127.');
    }
}
