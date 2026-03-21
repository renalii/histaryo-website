<?php

use App\Firebase\FirebaseMigration;
use Illuminate\Support\Facades\Storage;

return new class extends FirebaseMigration {
    public function up()
    {
        $landmarks = $this->firestore()->collection('landmarks')->documents();
        $maxBase64Bytes = 900000;

        foreach ($landmarks as $doc) {
            if (!$doc->exists()) {
                continue;
            }

            $data = $doc->data();
            $imagePath = $data['image_path'] ?? null;

            if (!empty($data['image_base64'])) {
                if (!empty($imagePath)) {
                    $doc->reference()->set([
                        'image_path' => null,
                    ], ['merge' => true]);
                }

                continue;
            }

            // Skip records without legacy file path or already migrated images.
            if (empty($imagePath)) {
                continue;
            }

            if (!Storage::disk('public')->exists($imagePath)) {
                continue;
            }

            $raw = Storage::disk('public')->get($imagePath);
            if ($raw === null || $raw === false) {
                continue;
            }

            $mime = Storage::disk('public')->mimeType($imagePath) ?: 'image/jpeg';
            [$encoded, $normalizedMime] = $this->prepareImageForFirestore($raw, $mime, $maxBase64Bytes);

            if ($encoded === null) {
                continue;
            }

            try {
                $doc->reference()->set([
                    'image_base64' => $encoded,
                    'image_mime' => $normalizedMime,
                    'image_path' => null,
                ], ['merge' => true]);
            } catch (\Throwable $e) {
                continue;
            }
        }
    }

    public function down()
    {
        // Intentionally left blank. This migration only adds base64 image fields.
    }

    private function prepareImageForFirestore(string $raw, string $mime, int $maxBase64Bytes): array
    {
        $encoded = base64_encode($raw);
        if (strlen($encoded) <= $maxBase64Bytes) {
            return [$encoded, $mime];
        }

        if (!function_exists('imagecreatefromstring')) {
            return [null, null];
        }

        $image = @imagecreatefromstring($raw);
        if ($image === false) {
            return [null, null];
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $quality = 82;
        $scale = 1.0;

        while ($scale >= 0.2) {
            $targetWidth = max(1, (int) round($width * $scale));
            $targetHeight = max(1, (int) round($height * $scale));
            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);
            imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

            ob_start();
            imagejpeg($canvas, null, $quality);
            $jpegData = ob_get_clean();
            imagedestroy($canvas);

            if ($jpegData !== false && $jpegData !== null) {
                $jpegBase64 = base64_encode($jpegData);
                if (strlen($jpegBase64) <= $maxBase64Bytes) {
                    imagedestroy($image);
                    return [$jpegBase64, 'image/jpeg'];
                }
            }

            $scale -= 0.15;
            if ($quality > 52) {
                $quality -= 8;
            }
        }

        imagedestroy($image);

        return [null, null];
    }
};
