<?php

namespace Tests\Unit;

use App\Services\CloudinaryImageService;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudinaryImageServiceTest extends TestCase
{
    public function test_it_returns_persistent_cloudinary_metadata(): void
    {
        config([
            'services.cloudinary.cloud_name' => 'demo',
            'services.cloudinary.api_key' => 'key',
            'services.cloudinary.api_secret' => 'secret',
            'services.cloudinary.landmark_folder' => 'histaryo/landmarks',
        ]);

        Http::fake([
            'https://api.cloudinary.com/v1_1/demo/image/upload' => Http::response([
                'secure_url' => 'https://res.cloudinary.com/demo/image/upload/optimized.webp',
                'public_id' => 'histaryo/landmarks/landmark-1',
            ]),
            'https://api.cloudinary.com/v1_1/demo/image/destroy' => Http::response(['result' => 'ok']),
        ]);

        $result = app(CloudinaryImageService::class)
            ->uploadLandmark(UploadedFile::fake()->createWithContent('source.jpg', 'source-image'), 'landmark-1');

        $this->assertSame([
            'image_path' => 'https://res.cloudinary.com/demo/image/upload/optimized.webp',
            'image_public_id' => 'histaryo/landmarks/landmark-1',
            'image_mime' => 'image/webp',
        ], $result);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.cloudinary.com/v1_1/demo/image/upload'
            && str_contains($request->body(), 'webp')
            && str_contains($request->body(), 'c_limit,w_1600,h_1600,q_auto:good'));
        Http::assertNotSent(fn (Request $request) => $request->url() === 'https://api.cloudinary.com/v1_1/demo/image/destroy');
        Http::assertSentCount(1);
    }

    public function test_it_deletes_a_persistent_cloudinary_landmark_image(): void
    {
        config([
            'services.cloudinary.cloud_name' => 'demo',
            'services.cloudinary.api_key' => 'key',
            'services.cloudinary.api_secret' => 'secret',
        ]);

        Http::fake([
            'https://api.cloudinary.com/v1_1/demo/image/destroy' => Http::response(['result' => 'ok']),
        ]);

        app(CloudinaryImageService::class)->deleteLandmark('histaryo/landmarks/landmark-1');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.cloudinary.com/v1_1/demo/image/destroy'
            && $request['public_id'] === 'histaryo/landmarks/landmark-1');
    }
}
