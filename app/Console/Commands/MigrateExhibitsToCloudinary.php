<?php

namespace App\Console\Commands;

use App\Services\CloudinaryImageService;
use App\Services\FirebaseService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class MigrateExhibitsToCloudinary extends Command
{
    protected $signature = 'exhibits:migrate-cloudinary {--dry-run : Show what would be migrated without uploading or changing Firestore}';

    protected $description = 'Move legacy local exhibit images to the Cloudinary Exhibits folder';

    public function __construct(
        private FirebaseService $firebase,
        private CloudinaryImageService $cloudinary,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $documents = $this->firebase->firestore()->collection('exhibits')->documents();
        $basePath = realpath(storage_path('app/public'));
        if ($basePath === false) {
            $this->error('Local public storage directory was not found.');

            return self::FAILURE;
        }

        $migrated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($documents as $document) {
            if (! $document->exists()) {
                continue;
            }

            $data = $document->data();
            $images = is_array($data['images'] ?? null) ? $data['images'] : [];
            $updatedImages = $images;
            $migratedLocalPaths = [];
            $changed = false;

            foreach ($images as $index => $image) {
                $legacyPath = trim((string) ($image['path'] ?? ''));
                if (! str_starts_with($legacyPath, 'local:')) {
                    continue;
                }

                $relativePath = ltrim(str_replace('\\', '/', substr($legacyPath, 6)), '/');
                $fullPath = realpath($basePath.DIRECTORY_SEPARATOR.$relativePath);
                if ($fullPath === false || ! $this->isWithin($fullPath, $basePath) || ! is_file($fullPath)) {
                    $this->error("Missing local file for exhibit {$document->id()}: {$relativePath}");
                    $failed++;

                    continue;
                }

                $this->line(($this->option('dry-run') ? '[dry-run] ' : '')."{$document->id()} <= {$relativePath}");
                if ($this->option('dry-run')) {
                    $skipped++;

                    continue;
                }

                try {
                    $uploaded = $this->cloudinary->uploadExhibit(
                        new UploadedFile($fullPath, basename($fullPath), mime_content_type($fullPath) ?: null, null, true),
                        (string) ($data['landmark_id'] ?? 'unknown-landmark'),
                        $document->id()
                    );

                    $updatedImages[$index] = $uploaded;
                    $migratedLocalPaths[] = $legacyPath;
                    $changed = true;
                    $migrated++;
                } catch (\Throwable $exception) {
                    report($exception);
                    $this->error("Upload failed for {$relativePath}: {$exception->getMessage()}");
                    $failed++;
                }
            }

            if (! $changed || $this->option('dry-run')) {
                continue;
            }

            try {
                $this->firebase->firestore()->collection('exhibits')->document($document->id())->set([
                    'images' => array_values($updatedImages),
                    'updated_at' => now()->toDateTimeString(),
                ], ['merge' => true]);

                foreach ($migratedLocalPaths as $legacyPath) {
                    $relativePath = ltrim(str_replace('\\', '/', substr($legacyPath, 6)), '/');
                    $fullPath = realpath($basePath.DIRECTORY_SEPARATOR.$relativePath);
                    if ($fullPath !== false && $this->isWithin($fullPath, $basePath)) {
                        File::delete($fullPath);
                    }
                }
            } catch (\Throwable $exception) {
                report($exception);
                $this->error("Firestore update failed for exhibit {$document->id()}: {$exception->getMessage()}");
                $failed++;
            }
        }

        $this->info("Migrated: {$migrated}; skipped: {$skipped}; failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function isWithin(string $path, string $basePath): bool
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $basePath = rtrim(str_replace('\\', '/', $basePath), '/');

        return $path === $basePath || str_starts_with($path, $basePath.'/');
    }
}
