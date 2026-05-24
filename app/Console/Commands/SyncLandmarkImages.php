<?php

namespace App\Console\Commands;

use App\Services\FirebaseService;
use App\Services\LandmarkImageStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncLandmarkImages extends Command
{
    protected $signature = 'landmarks:sync-images {--fresh : Rebuild the landmarks folder from Firestore images}';

    protected $description = 'Sync Firestore landmark base64 images to storage/app/public/landmarks';

    public function __construct(private readonly FirebaseService $firebaseService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $dir = 'landmarks';

        if ($this->option('fresh')) {
            $existing = $disk->files($dir);
            if (!empty($existing)) {
                $disk->delete($existing);
            }
            $this->info('Cleared existing files in public/landmarks.');
        }

        if (!$disk->exists($dir)) {
            $disk->makeDirectory($dir);
        }

        $docs = $this->firebaseService->firestore()->collection('landmarks')->documents();

        $totalDocs = 0;
        $written = 0;
        $skippedNoImage = 0;
        $skippedDecode = 0;

        foreach ($docs as $doc) {
            if (!$doc->exists()) {
                continue;
            }

            $totalDocs++;
            $data = $doc->data();
            $base64 = $data['image_base64'] ?? null;
            $mime = $data['image_mime'] ?? 'image/jpeg';

            if (empty($base64)) {
                $skippedNoImage++;
                continue;
            }

            if (LandmarkImageStorage::persistFromBase64($doc->id(), $base64, $mime)) {
                $written++;
            } else {
                $skippedDecode++;
            }
        }

        $finalCount = count($disk->files($dir));

        $this->info("Landmarks in Firestore: {$totalDocs}");
        $this->info("Images written: {$written}");
        $this->info("Skipped (no image_base64): {$skippedNoImage}");
        $this->info("Skipped (invalid base64): {$skippedDecode}");
        $this->info("Files currently in public/landmarks: {$finalCount}");

        return self::SUCCESS;
    }
}
