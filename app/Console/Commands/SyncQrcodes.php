<?php

namespace App\Console\Commands;

use App\Services\FirebaseService;
use App\Support\QrResolveUrl;
use Google\Cloud\Firestore\FieldValue;
use Illuminate\Console\Command;

class SyncQrcodes extends Command
{
    protected $signature = 'qrcodes:sync';

    protected $description = 'Create or update qrcodes documents for landmarks with QR codes.';

    public function handle(FirebaseService $firebase): int
    {
        $firestore = $firebase->firestore();
        $landmarks = $firestore->collection('landmarks')->documents();
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($landmarks as $landmark) {
            if (! $landmark->exists()) {
                continue;
            }

            $data = $landmark->data();
            $code = $this->landmarkCode($data);
            if ($code === null) {
                $skipped++;
                continue;
            }

            $docRef = $firestore->collection('qrcodes')->document($code);
            $doc = $docRef->snapshot();
            $payload = [
                'code' => $code,
                'landmarkId' => $landmark->id(),
                'landmarkCode' => $code,
                'landmarkName' => (string) ($data['name'] ?? 'Untitled'),
                'qrUrl' => QrResolveUrl::forCode($code),
                'status' => 'active',
            ];

            if (! $doc->exists() || ! array_key_exists('createdAt', $doc->data())) {
                $payload['createdAt'] = FieldValue::serverTimestamp();
                $created++;
            } else {
                $updated++;
            }

            $docRef->set($payload, ['merge' => true]);
        }

        $this->info("qrcodes sync complete. Created: {$created}. Updated: {$updated}. Skipped: {$skipped}.");

        return self::SUCCESS;
    }

    private function landmarkCode(array $landmarkData): ?string
    {
        foreach (['code', 'landmarkcode', 'landmark_code', 'landmarkCode', 'qr_code', 'qrCode'] as $field) {
            $code = trim((string) ($landmarkData[$field] ?? ''));
            if ($code !== '') {
                return $code;
            }
        }

        return null;
    }
}
