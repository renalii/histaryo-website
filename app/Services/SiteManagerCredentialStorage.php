<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class SiteManagerCredentialStorage
{
    /** @return array{filename:string,mime:string,path:string,url:string,uploaded_at:string} */
    public function store(UploadedFile $file, string $uid): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'credentials';
        $path = 'site-manager-credentials/'.trim($uid, '/').'/'.now()->format('YmdHis').'-'.Str::random(10).'-'.$baseName.'.'.$extension;
        $mime = $file->getMimeType() ?: 'application/octet-stream';

        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        return [
            'filename' => $file->getClientOriginalName(),
            'mime' => $mime,
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'uploaded_at' => now()->toDateTimeString(),
        ];
    }
}
