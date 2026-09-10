<?php

namespace App\Console\Commands;

use App\Services\CloudinaryImageService;
use App\Services\FirebaseService;
use Illuminate\Console\Command;

class MigrateLandmarksToCloudinaryFolder extends Command
{
    protected $signature = 'landmarks:migrate-cloudinary-folder {--dry-run : Show what would be moved without uploading or changing Firestore}';

    protected $description = 'Move existing landmark images into the Cloudinary landmarks folder';

    public function __construct(
        private FirebaseService $firebase,
        private CloudinaryImageService $cloudinary,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $moved = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($this->firebase->firestore()->collection('landmarks')->documents() as $document) {
            if (! $document->exists()) {
                continue;
            }

            $data = $document->data();
            $url = trim((string) ($data['image_path'] ?? ''));
            $publicId = trim((string) ($data['image_public_id'] ?? ''));
            if ($url === '' || $publicId === '') {
                $skipped++;

                continue;
            }

            $this->line(($this->option('dry-run') ? '[dry-run] ' : '').$document->id().' <= '.$publicId);
            if ($this->option('dry-run')) {
                $skipped++;

                continue;
            }

            try {
                $result = $this->cloudinary->moveLandmarkToAssetFolder($url, $publicId);
                $this->firebase->firestore()->collection('landmarks')->document($document->id())->set([
                    'image_path' => $result['image_path'],
                    'image_public_id' => $result['image_public_id'],
                    'image_mime' => $result['image_mime'],
                ], ['merge' => true]);
                $moved++;
            } catch (\Throwable $exception) {
                report($exception);
                $this->error("Migration failed for {$document->id()}: {$exception->getMessage()}");
                $failed++;
            }
        }

        $this->info("Moved: {$moved}; skipped: {$skipped}; failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
