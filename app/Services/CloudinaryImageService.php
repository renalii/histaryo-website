<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

final class CloudinaryImageService
{
    /** @return array{image_path: string, image_public_id: string, image_mime: string} */
    public function uploadLandmark(UploadedFile $file, string $landmarkId): array
    {
        return $this->optimize(
            file_get_contents($file->getRealPath()),
            $this->landmarkPublicId($landmarkId),
            $file->getClientOriginalName(),
            trim((string) config('services.cloudinary.landmark_asset_folder', 'landmarks'), '/')
        );
    }

    /** @return array{image_path: string, image_public_id: string, image_mime: string} */
    public function moveLandmarkToAssetFolder(string $url, string $publicId): array
    {
        $this->assertConfigured();
        $response = Http::timeout(30)->get($url);
        $response->throw();

        return $this->optimize(
            $response->body(),
            $publicId,
            basename(parse_url($url, PHP_URL_PATH) ?: 'landmark-image'),
            trim((string) config('services.cloudinary.landmark_asset_folder', 'landmarks'), '/')
        );
    }

    public function deleteLandmark(string $publicId): void
    {
        if (trim($publicId) === '') {
            return;
        }

        $this->deleteImage($publicId);
    }

    /** @return array{filename:string,mime:string,path:string,url:string,provider:string,uploaded_at:string} */
    public function uploadExhibit(UploadedFile $file, string $landmarkId, string $exhibitId): array
    {
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'media';
        $publicId = trim((string) config('services.cloudinary.exhibit_folder', 'Exhibits'), '/')
            .'/'.trim($landmarkId, '/')
            .'/'.trim($exhibitId, '/')
            .'/'.now()->format('YmdHis').'-'.Str::random(10).'-'.$filename;

        $result = $this->optimize(
            file_get_contents($file->getRealPath()),
            $publicId,
            $file->getClientOriginalName(),
            trim((string) config('services.cloudinary.exhibit_folder', 'Exhibits'), '/')
        );

        return [
            'filename' => $file->getClientOriginalName(),
            'mime' => $result['image_mime'],
            'path' => $result['image_public_id'],
            'url' => $result['image_path'],
            'provider' => 'cloudinary',
            'uploaded_at' => now()->toDateTimeString(),
        ];
    }

    public function deleteImage(string $publicId): void
    {
        if (trim($publicId) === '') {
            return;
        }

        $this->assertConfigured();
        $this->delete($publicId);
    }

    /** @return array{image_path: string, image_public_id: string, image_mime: string} */
    private function optimize(string|false $binary, string $publicId, string $filename, ?string $assetFolder = null): array
    {
        $this->assertConfigured();

        if ($binary === false || $binary === '') {
            throw new RuntimeException('The landmark image could not be read.');
        }

        $params = [
            'invalidate' => 'true',
            'overwrite' => 'true',
            'public_id' => $publicId,
            'timestamp' => time(),
            'transformation' => 'c_limit,w_1600,h_1600,q_auto:good',
            'format' => 'webp',
        ];
        if ($assetFolder !== null && $assetFolder !== '') {
            $params['asset_folder'] = $assetFolder;
        }

        $response = Http::attach('file', $binary, $filename)
            ->post($this->apiUrl('image/upload'), array_merge($params, [
                'api_key' => config('services.cloudinary.api_key'),
                'signature' => $this->signature($params),
            ]));

        $response->throw();

        $url = trim((string) $response->json('secure_url'));
        $storedPublicId = trim((string) $response->json('public_id', $publicId));
        if ($url === '' || $storedPublicId === '') {
            throw new RuntimeException('Cloudinary did not return the required image metadata.');
        }

        return [
            'image_path' => $url,
            'image_public_id' => $storedPublicId,
            'image_mime' => 'image/webp',
        ];
    }

    private function landmarkPublicId(string $landmarkId): string
    {
        return trim((string) config('services.cloudinary.landmark_folder', 'histaryo/landmarks'), '/')
            .'/'.trim($landmarkId);
    }

    private function apiUrl(string $action): string
    {
        return 'https://api.cloudinary.com/v1_1/'.config('services.cloudinary.cloud_name').'/'.$action;
    }

    private function delete(string $publicId): void
    {
        $params = [
            'invalidate' => 'true',
            'public_id' => $publicId,
            'timestamp' => time(),
        ];

        $response = Http::asForm()->post($this->apiUrl('image/destroy'), array_merge($params, [
            'api_key' => config('services.cloudinary.api_key'),
            'signature' => $this->signature($params),
        ]));

        $response->throw();
    }

    private function signature(array $params): string
    {
        ksort($params);
        $parts = [];
        foreach ($params as $key => $value) {
            $parts[] = $key.'='.$value;
        }

        return sha1(implode('&', $parts).config('services.cloudinary.api_secret'));
    }

    private function assertConfigured(): void
    {
        foreach (['cloud_name', 'api_key', 'api_secret'] as $key) {
            if (blank(config('services.cloudinary.'.$key))) {
                throw new RuntimeException('Cloudinary is not configured. Set CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, and CLOUDINARY_API_SECRET.');
            }
        }
    }
}
