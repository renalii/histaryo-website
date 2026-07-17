<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

final class QrCodeImageStorage
{
    private const DISK = 'public';
    private const DIR = 'qrcodes';

    public static function pathFor(string $preferredIdentifier, string $fallbackIdentifier = 'qr-code'): string
    {
        $filename = self::safeFilename($preferredIdentifier);
        if ($filename === '') {
            $filename = self::safeFilename($fallbackIdentifier);
        }
        if ($filename === '') {
            $filename = 'qr-code';
        }

        return self::DIR.'/'.$filename.'.png';
    }

    public static function putPng(string $path, string $png): bool
    {
        $disk = Storage::disk(self::DISK);
        if (! $disk->exists(self::DIR)) {
            $disk->makeDirectory(self::DIR);
        }

        return $disk->put($path, $png) !== false;
    }

    public static function exists(string $path): bool
    {
        return $path !== '' && Storage::disk(self::DISK)->exists($path);
    }

    public static function get(string $path): string
    {
        return Storage::disk(self::DISK)->get($path);
    }

    public static function absolutePath(string $path): string
    {
        return Storage::disk(self::DISK)->path($path);
    }

    public static function url(string $path): string
    {
        return Storage::disk(self::DISK)->url($path);
    }

    public static function deletePath(?string $path): void
    {
        $path = trim((string) $path);
        if ($path === '') {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }

    public static function deleteFor(string $preferredIdentifier, string $fallbackIdentifier = 'qr-code'): void
    {
        self::deletePath(self::pathFor($preferredIdentifier, $fallbackIdentifier));
    }

    private static function safeFilename(string $value): string
    {
        $filename = trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $value), '-');

        return strtolower($filename);
    }
}
