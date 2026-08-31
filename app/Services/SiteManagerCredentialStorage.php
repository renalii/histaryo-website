<?php

namespace App\Services;

use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class SiteManagerCredentialStorage
{
    public function __construct(private FirebaseService $firebase) {}

    /** @return array{filename:string,mime:string,path:string,url:string,uploaded_at:string} */
    public function store(UploadedFile $file, string $uid): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'credentials';
        $path = 'site-manager-credentials/'.trim($uid, '/').'/'.now()->format('YmdHis').'-'.Str::random(10).'-'.$baseName.'.'.$extension;
        $mime = $file->getMimeType() ?: 'application/octet-stream';

        try {
            $object = $this->firebase->storage()->getBucket()->upload(fopen($file->getRealPath(), 'r'), [
                'name' => $path,
                'metadata' => ['contentType' => $mime],
            ]);

            return [
                'filename' => $file->getClientOriginalName(),
                'mime' => $mime,
                'path' => $path,
                'url' => (string) $object->signedUrl(new DateTimeImmutable('+1 year')),
                'uploaded_at' => now()->toDateTimeString(),
            ];
        } catch (\Throwable $exception) {
            report($exception);
            Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

            return [
                'filename' => $file->getClientOriginalName(),
                'mime' => $mime,
                'path' => 'local:'.$path,
                'url' => '',
                'uploaded_at' => now()->toDateTimeString(),
            ];
        }
    }
}
