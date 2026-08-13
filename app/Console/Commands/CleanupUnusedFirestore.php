<?php

namespace App\Console\Commands;

use App\Services\FirebaseService;
use Google\Cloud\Firestore\FieldValue;
use Illuminate\Console\Command;

class CleanupUnusedFirestore extends Command
{
    protected $signature = 'histaryo:cleanup-unused-firestore
                            {--force : Run without asking for confirmation}';

    protected $description = 'Remove unused exhibit videos, landmark tags, and Firestore audit logs';

    public function __construct(private FirebaseService $firebase)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm(
            'Permanently remove exhibit video fields, landmark tag fields, and all Firestore audit logs?',
            false
        )) {
            $this->components->info('Cleanup cancelled.');

            return self::SUCCESS;
        }

        try {
            $firestore = $this->firebase->firestore();
        } catch (\Throwable $exception) {
            $this->components->error('Could not connect to Firestore: '.$exception->getMessage());

            return self::FAILURE;
        }

        [$exhibitCount, $exhibitErrors] = $this->removeField(
            $firestore,
            'exhibits',
            'video',
            'exhibits'
        );
        [$landmarkCount, $landmarkErrors] = $this->removeField(
            $firestore,
            'landmarks',
            'tags',
            'landmarks'
        );
        [$logCount, $logErrors] = $this->deleteCollectionDocuments($firestore, 'logs');

        $this->newLine();
        $this->components->info("Removed video field from {$exhibitCount} exhibits");
        $this->components->info("Removed tags field from {$landmarkCount} landmarks");
        $this->components->info("Deleted {$logCount} log documents");

        $errors = $exhibitErrors + $landmarkErrors + $logErrors;
        if ($errors > 0) {
            $this->components->warn("Cleanup completed with {$errors} document error(s).");

            return self::FAILURE;
        }

        $this->components->info('Firestore cleanup completed successfully.');

        return self::SUCCESS;
    }

    /**
     * @return array{0:int,1:int}
     */
    private function removeField(
        object $firestore,
        string $collection,
        string $field,
        string $label
    ): array {
        try {
            $documents = iterator_to_array($firestore->collection($collection)->documents());
        } catch (\Throwable $exception) {
            $this->components->error("Could not read {$collection}: ".$exception->getMessage());

            return [0, 1];
        }

        $removed = 0;
        $errors = 0;
        $progress = $this->output->createProgressBar(count($documents));
        $progress->setMessage("Checking {$label}");
        $progress->start();

        foreach ($documents as $document) {
            try {
                if ($document->exists() && array_key_exists($field, $document->data())) {
                    $document->reference()->update([
                        ['path' => $field, 'value' => FieldValue::deleteField()],
                    ]);
                    $removed++;
                }
            } catch (\Throwable $exception) {
                $errors++;
                $this->newLine();
                $this->components->warn(
                    "Could not remove {$field} from {$collection}/{$document->id()}: ".$exception->getMessage()
                );
            } finally {
                $progress->advance();
            }
        }

        $progress->finish();
        $this->newLine();

        return [$removed, $errors];
    }

    /**
     * @return array{0:int,1:int}
     */
    private function deleteCollectionDocuments(object $firestore, string $collection): array
    {
        try {
            $documents = iterator_to_array($firestore->collection($collection)->documents());
        } catch (\Throwable $exception) {
            $this->components->error("Could not read {$collection}: ".$exception->getMessage());

            return [0, 1];
        }

        $deleted = 0;
        $errors = 0;
        $progress = $this->output->createProgressBar(count($documents));
        $progress->setMessage("Deleting {$collection}");
        $progress->start();

        foreach ($documents as $document) {
            try {
                if ($document->exists()) {
                    $document->reference()->delete();
                    $deleted++;
                }
            } catch (\Throwable $exception) {
                $errors++;
                $this->newLine();
                $this->components->warn(
                    "Could not delete {$collection}/{$document->id()}: ".$exception->getMessage()
                );
            } finally {
                $progress->advance();
            }
        }

        $progress->finish();
        $this->newLine();

        return [$deleted, $errors];
    }
}
