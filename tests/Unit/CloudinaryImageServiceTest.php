<?php

namespace Tests\Unit;

use App\Services\CloudinaryImageService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudinaryImageServiceTest extends TestCase
{
    public function test_it_returns_persistent_cloudinary_metadata_and_an_optimized_webp_data_uri(): void
    {
        config([
            'services.cloudinary.cloud_name' => 'demo',
            'services.cloudinary.api_key' => 'key',
            'services.cloudinary.api_secret' => 'secret',
            'services.cloudinary.landmark_folder' => 'histaryo/landmarks',
            'services.cloudinary.max_base64_bytes' => 700000,
        ]);

        Http::fake([
            'https://api.cloudinary.com/v1_1/demo/image/upload' => Http::response([
                'secure_url' => 'https://res.cloudinary.com/demo/image/upload/optimized.webp',
                'public_id' => 'histaryo/landmarks/landmark-1',
            ]),
            'https://res.cloudinary.com/*' => Http::response('optimized-webp'),
            'https://api.cloudinary.com/v1_1/demo/image/destroy' => Http::response(['result' => 'ok']),
        ]);

        $result = app(CloudinaryImageService::class)
            ->uploadLandmarkBase64(base64_encode('source-image'), 'landmark-1');

        $this->assertSame([
            'image_url' => 'https://res.cloudinary.com/demo/image/upload/optimized.webp',
            'image_public_id' => 'histaryo/landmarks/landmark-1',
            'image_base64' => 'data:image/webp;base64,'.base64_encode('optimized-webp'),
            'image_mime' => 'image/webp',
        ], $result);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.cloudinary.com/v1_1/demo/image/upload'
            && str_contains($request->body(), 'webp')
            && str_contains($request->body(), 'c_limit,w_1600,h_1600,q_auto:good'));
        Http::assertNotSent(fn (Request $request) => $request->url() === 'https://api.cloudinary.com/v1_1/demo/image/destroy');
        Http::assertSentCount(2);
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
