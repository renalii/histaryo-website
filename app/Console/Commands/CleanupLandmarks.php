<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FirebaseService;
use Google\Cloud\Firestore\FieldValue;

class CleanupLandmarks extends Command
{
    protected $signature = 'landmarks:cleanup';
    protected $description = 'Remove old lati/longti fields from landmarks in Firestore';

    protected $firestore;

    public function __construct(FirebaseService $firebaseService)
    {
        parent::__construct();
        $this->firestore = $firebaseService->firestore();
    }

    public function handle()
    {
        $landmarksRef = $this->firestore->collection('landmarks');
        $documents = $landmarksRef->documents();

        $updatedCount = 0;

        foreach ($documents as $doc) {
            if (!$doc->exists()) {
                continue;
            }

            $updates = [];

            if ($doc->exists()) {
                $data = $doc->data();

                if (array_key_exists('lati', $data)) {
                    $updates[] = ['path' => 'lati', 'value' => FieldValue::deleteField()];
                }
                if (array_key_exists('longti', $data)) {
                    $updates[] = ['path' => 'longti', 'value' => FieldValue::deleteField()];
                }

                if (!empty($updates)) {
                    $doc->reference()->update($updates);
                    $this->info("Cleaned document: {$doc->id()}");
                    $updatedCount++;
                }
            }
        }

        $this->info("Cleanup complete. {$updatedCount} documents updated.");
    }
}
