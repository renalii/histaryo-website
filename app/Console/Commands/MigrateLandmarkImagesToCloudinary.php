<?php

namespace App\Console\Commands;

use App\Services\CloudinaryImageService;
use App\Services\FirebaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateLandmarkImagesToCloudinary extends Command
{
    protected $signature = 'landmarks:migrate-images-to-cloudinary';

    protected $description = 'Upload existing landmark images to Cloudinary and save Cloudinary metadata plus WebP Base64 in Firestore';

    public function __construct(
        private readonly FirebaseService $firebase,
        private readonly CloudinaryImageService $cloudinary,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $uploaded = 0;
        $skipped = 0;

        foreach ($this->firebase->firestore()->collection('landmarks')->documents() as $doc) {
            if (! $doc->exists()) {
                continue;
            }

            $data = $doc->data();
            $previousImagePublicId = trim((string) ($data['image_public_id'] ?? ''));
            $base64 = trim((string) ($data['image_base64'] ?? ''));
            $imageUrl = trim((string) ($data['image_url'] ?? ''));
            $localBase64 = $base64 === '' && $imageUrl === ''
                ? $this->localImageAsBase64($doc->id())
                : '';

            if ($base64 !== '') {
                $imageData = $this->cloudinary->uploadLandmarkBase64($base64, $doc->id());
            } elseif ($imageUrl !== '') {
                $imageData = $this->cloudinary->uploadLandmarkUrl($imageUrl, $doc->id());
            } elseif ($localBase64 !== '') {
                $imageData = $this->cloudinary->uploadLandmarkBase64($localBase64, $doc->id());
            } else {
                $imageData = [];
            }

            if ($imageData === []) {
                $skipped++;
                continue;
            }

            $doc->reference()->set($imageData, ['merge' => true]);
            if ($previousImagePublicId !== '' && $previousImagePublicId !== $imageData['image_public_id']) {
                $this->cloudinary->deleteLandmark($previousImagePublicId);
            }
            $this->deleteLocalImage($doc->id());
            $uploaded++;
            $this->line('Optimized '.$doc->id());
        }

        $this->info("Optimized: {$uploaded}; skipped: {$skipped}");

        return self::SUCCESS;
    }

    private function localImageAsBase64(string $landmarkId): string
    {
        foreach (['jpg', 'jpeg', 'png', 'webp', 'gif'] as $extension) {
            $path = 'landmarks/'.$landmarkId.'.'.$extension;
            if (Storage::disk('public')->exists($path)) {
                return base64_encode(Storage::disk('public')->get($path));
            }
        }

        return '';
    }

    private function deleteLocalImage(string $landmarkId): void
    {
        foreach (['jpg', 'jpeg', 'png', 'webp', 'gif'] as $extension) {
            Storage::disk('public')->delete('landmarks/'.$landmarkId.'.'.$extension);
        }
    }

}
