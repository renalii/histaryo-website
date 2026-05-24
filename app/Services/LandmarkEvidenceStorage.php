<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class LandmarkEvidenceStorage
{
    private const DISK = 'public';

    /**
     * Save uploads under storage/app/public/landmark-evidence/{landmarkId}/.
     *
     * @param  list<UploadedFile>  $files
     * @return list<array{filename: string, mime: string, storage_path: string, uploaded_at: string}>
     */
    public static function storeForLandmark(string $landmarkId, array $files): array
    {
        $landmarkId = trim($landmarkId);
        if ($landmarkId === '') {
            return [];
        }

        $documents = [];
        $baseDir = 'landmark-evidence/'.$landmarkId;

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $original = $file->getClientOriginalName();
            $safeName = self::safeFilename($original);
            $relativePath = $baseDir.'/'.$safeName;

            Storage::disk(self::DISK)->putFileAs($baseDir, $file, $safeName);

            $documents[] = [
                'filename' => $original,
                'mime' => $file->getMimeType() ?: 'application/octet-stream',
                'storage_path' => $relativePath,
                'uploaded_at' => now()->toDateTimeString(),
            ];
        }

        return $documents;
    }

    public static function publicUrl(array $document): ?string
    {
        $path = trim((string) ($document['storage_path'] ?? ''));
        if ($path === '' || ! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        return Storage::disk(self::DISK)->url($path);
    }

    private static function safeFilename(string $name): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $slug = Str::slug($base);
        if ($slug === '') {
            $slug = 'document';
        }
        $suffix = Str::lower(Str::random(6));

        return $ext !== ''
            ? $slug.'-'.$suffix.'.'.Str::lower($ext)
            : $slug.'-'.$suffix;
    }
}
