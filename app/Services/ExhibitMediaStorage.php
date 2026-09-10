<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class ExhibitMediaStorage
{
    public function __construct(
        private FirebaseService $firebase,
        private CloudinaryImageService $cloudinary,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     * @return list<array{filename:string,mime:string,path:string,url:string,provider:string,uploaded_at:string}>
     */
    public function storeImages(string $landmarkId, string $exhibitId, array $files): array
    {
        $stored = [];
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $stored[] = $this->cloudinary->uploadExhibit($file, $landmarkId, $exhibitId);
        }

        return $stored;
    }

    /** @param list<array<string,mixed>> $media */
    public function deleteMany(array $media): void
    {
        foreach ($media as $item) {
            if (strtolower((string) ($item['provider'] ?? '')) === 'cloudinary') {
                try {
                    $this->cloudinary->deleteImage((string) ($item['path'] ?? ''));
                } catch (\Throwable $e) {
                    report($e);
                }

                continue;
            }

            $this->deletePath((string) ($item['path'] ?? ''));
        }
    }

    public function deletePath(string $path): void
    {
        $path = trim($path);
        if ($path === '') {
            return;
        }

        if (str_starts_with($path, 'local:')) {
            Storage::disk('public')->delete(substr($path, 6));

            return;
        }

        // New exhibit media stores the Cloudinary public ID in `path`.
        if (str_starts_with($path, 'Exhibits/')) {
            try {
                $this->cloudinary->deleteImage($path);
            } catch (\Throwable $e) {
                report($e);
            }

            return;
        }

        try {
            $this->firebase->storage()->getBucket()->object($path)->delete();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
