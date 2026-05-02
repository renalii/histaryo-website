<?php

namespace App\Services;

use App\Support\QrResolveUrl;
use Google\Cloud\Firestore\FieldValue;
use Illuminate\Support\Facades\Storage;

class LandmarkJoinQrService
{
    public function __construct(private FirebaseService $firebase) {}

    /**
     * Ensure the landmark has a curator join code document (LM-xxxxxx style) and QR image file.
     */
    public function ensureJoinQrForLandmark(string $landmarkId): string
    {
        $fs = $this->firebase->firestore();

        $existing = $fs->collection('qr_codes')->where('landmark_id', '==', $landmarkId)->documents();
        foreach ($existing as $doc) {
            if (! $doc->exists()) {
                continue;
            }
            $code = (string) ($doc['code'] ?? '');
            if ($code !== '' && preg_match('/^LM-/i', $code)) {
                $this->generateQrImageFile($code, 'png');

                return $code;
            }
        }

        $code = 'LM-'.substr($landmarkId, 0, 6);
        $codeExists = $fs->collection('qr_codes')->where('code', '==', $code)->limit(1)->documents();
        foreach ($codeExists as $ex) {
            if ($ex->exists()) {
                $code = 'LM-'.substr(sha1($landmarkId.microtime(true)), 0, 6);

                break;
            }
        }

        $fs->collection('qr_codes')->add([
            'code' => $code,
            'landmark_id' => $landmarkId,
            'is_auto' => true,
            'is_landmark_join_code' => true,
            'created_at' => FieldValue::serverTimestamp(),
        ]);

        $this->generateQrImageFile($code, 'png');

        return $code;
    }

    private function generateQrImageFile(string $code, string $format = 'png'): bool
    {
        $dir = 'qrcodes';
        $ext = in_array($format, ['png', 'svg'], true) ? $format : 'png';
        $path = "{$dir}/{$code}.{$ext}";
        try {
            if (! Storage::disk('public')->exists($dir)) {
                Storage::disk('public')->makeDirectory($dir);
            }

            $url = QrResolveUrl::forCode($code);

            if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
                $qr = \SimpleSoftwareIO\QrCode\Facades\QrCode::format($ext)
                    ->size(600)->margin(1)->generate($url);

                Storage::disk('public')->put($path, $qr);

                return true;
            }

            if ($ext === 'svg') {
                $safe = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
                $svg = <<<SVG
                    <svg xmlns="http://www.w3.org/2000/svg" width="600" height="600">
                    <rect width="100%" height="100%" fill="#ffffff"/>
                    <rect x="10" y="10" width="580" height="580" fill="none" stroke="#000" stroke-width="6"/>
                    <text x="50%" y="50%" font-family="monospace" font-size="18" text-anchor="middle">
                        {$safe}
                    </text>
                    </svg>
                    SVG;
                Storage::disk('public')->put($path, $svg);

                return true;
            }

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
