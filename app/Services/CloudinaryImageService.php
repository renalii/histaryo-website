<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class CloudinaryImageService
{
    /**
     * @return array{image_url: string, image_public_id: string, image_base64: string, image_mime: string}
     */
    public function uploadLandmark(UploadedFile $file, string $landmarkId): array
    {
        return $this->optimize(
            file_get_contents($file->getRealPath()),
            $this->landmarkPublicId($landmarkId),
            $file->getClientOriginalName()
        );
    }

    /**
     * @return array{image_url: string, image_public_id: string, image_base64: string, image_mime: string}
     */
    public function uploadLandmarkBase64(string $base64, string $landmarkId): array
    {
        if (str_contains($base64, ',')) {
            $base64 = explode(',', $base64, 2)[1] ?? '';
        }

        $binary = base64_decode($base64, true);
        if ($binary === false) {
            throw new RuntimeException('The landmark image contains invalid base64 data.');
        }

        return $this->optimize($binary, $this->landmarkPublicId($landmarkId), $landmarkId.'.jpg');
    }

    /**
     * @return array{image_url: string, image_public_id: string, image_base64: string, image_mime: string}
     */
    public function uploadLandmarkUrl(string $url, string $landmarkId): array
    {
        $response = Http::get($url);
        $response->throw();

        return $this->optimize($response->body(), $this->landmarkPublicId($landmarkId), $landmarkId.'.jpg');
    }

    public function deleteLandmark(string $publicId): void
    {
        if (trim($publicId) === '') {
            return;
        }

        $this->assertConfigured();
        $this->delete($publicId);
    }

    /**
     * @return array{image_url: string, image_public_id: string, image_base64: string, image_mime: string}
     */
    private function optimize(string|false $binary, string $publicId, string $filename): array
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

        $optimized = Http::get($url);
        $optimized->throw();

        $dataUri = 'data:image/webp;base64,'.base64_encode($optimized->body());
        $maxBytes = (int) config('services.cloudinary.max_base64_bytes', 700000);
        if (strlen($dataUri) > $maxBytes) {
            throw new RuntimeException('The optimized image is too large to store safely in Firestore.');
        }

        return [
            'image_url' => $url,
            'image_public_id' => $storedPublicId,
            'image_base64' => $dataUri,
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
