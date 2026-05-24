<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

final class LandmarkImageStorage
{
    private const DISK = 'public';

    private const DIR = 'landmarks';

    /**
     * Write landmark photo to storage/app/public/landmarks/{landmarkId}.{ext}.
     */
    public static function persistFromBase64(string $landmarkId, string $base64, ?string $mimeType = null): bool
    {
        $landmarkId = trim($landmarkId);
        if ($landmarkId === '') {
            return false;
        }

        if (str_contains($base64, ',')) {
            $parts = explode(',', $base64, 2);
            $base64 = $parts[1] ?? '';
        }

        $binary = base64_decode($base64, true);
        if ($binary === false) {
            return false;
        }

        self::deleteForLandmark($landmarkId);

        $ext = self::extensionFromMime($mimeType ?: 'image/jpeg');
        Storage::disk(self::DISK)->put(self::DIR.'/'.$landmarkId.'.'.$ext, $binary);

        return true;
    }

    public static function deleteForLandmark(string $landmarkId): void
    {
        $landmarkId = trim($landmarkId);
        if ($landmarkId === '') {
            return;
        }

        $disk = Storage::disk(self::DISK);
        foreach (['jpg', 'jpeg', 'png', 'webp', 'gif'] as $ext) {
            $path = self::DIR.'/'.$landmarkId.'.'.$ext;
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }

    public static function publicUrl(string $landmarkId): ?string
    {
        $landmarkId = trim($landmarkId);
        if ($landmarkId === '') {
            return null;
        }

        $disk = Storage::disk(self::DISK);
        foreach (['jpg', 'jpeg', 'png', 'webp', 'gif'] as $ext) {
            $path = self::DIR.'/'.$landmarkId.'.'.$ext;
            if ($disk->exists($path)) {
                return $disk->url($path);
            }
        }

        return null;
    }

    public static function extensionFromMime(string $mime): string
    {
        return match (strtolower(trim($mime))) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/jpeg', 'image/jpg' => 'jpg',
            default => 'jpg',
        };
    }
}
