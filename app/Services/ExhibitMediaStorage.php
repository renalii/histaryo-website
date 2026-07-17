<?php

namespace App\Services;

use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ExhibitMediaStorage
{
    public function __construct(private FirebaseService $firebase) {}

    /**
     * @param  list<UploadedFile>  $files
     * @return list<array{filename:string,mime:string,path:string,url:string,uploaded_at:string}>
     */
    public function storeImages(string $landmarkId, string $exhibitId, array $files): array
    {
        $stored = [];
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $stored[] = $this->storeFile($file, $this->basePath($landmarkId, $exhibitId).'/images');
        }

        return $stored;
    }

    /** @param list<array<string,mixed>> $media */
    public function deleteMany(array $media): void
    {
        foreach ($media as $item) {
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

        try {
            $this->firebase->storage()->getBucket()->object($path)->delete();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** @return array{filename:string,mime:string,path:string,url:string,uploaded_at:string} */
    private function storeFile(UploadedFile $file, string $directory): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        if ($filename === '') {
            $filename = 'media';
        }

        $path = trim($directory, '/').'/'.now()->format('YmdHis').'-'.Str::random(10).'-'.$filename.'.'.$extension;

        try {
            $bucket = $this->firebase->storage()->getBucket();
            $object = $bucket->upload(fopen($file->getRealPath(), 'r'), [
                'name' => $path,
                'metadata' => [
                    'contentType' => $file->getMimeType() ?: 'application/octet-stream',
                ],
            ]);

            return [
                'filename' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType() ?: 'application/octet-stream',
                'path' => $path,
                'url' => $this->signedUrl($object),
                'uploaded_at' => now()->toDateTimeString(),
            ];
        } catch (\Throwable $e) {
            report($e);

            return $this->storeFileLocally($file, $path);
        }
    }

    /** @return array{filename:string,mime:string,path:string,url:string,uploaded_at:string} */
    private function storeFileLocally(UploadedFile $file, string $path): array
    {
        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        return [
            'filename' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?: 'application/octet-stream',
            'path' => 'local:'.$path,
            'url' => Storage::disk('public')->url($path),
            'uploaded_at' => now()->toDateTimeString(),
        ];
    }

    private function signedUrl(object $object): string
    {
        try {
            return (string) $object->signedUrl(new DateTimeImmutable('+1 year'));
        } catch (\Throwable $e) {
            report($e);

            return '';
        }
    }

    private function basePath(string $landmarkId, string $exhibitId): string
    {
        return 'exhibits/'.trim($landmarkId, '/').'/'.trim($exhibitId, '/');
    }
}
