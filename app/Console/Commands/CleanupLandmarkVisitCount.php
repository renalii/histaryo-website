<?php

namespace App\Console\Commands;

use App\Services\FirebaseService;
use Google\Cloud\Firestore\FieldValue;
use Illuminate\Console\Command;

class CleanupLandmarkVisitCount extends Command
{
    protected $signature = 'landmarks:cleanup-visit-count {--dry-run : Report changes without updating Firestore}';

    protected $description = 'Remove duplicate visitCount fields from Firestore landmark documents';

    public function __construct(private readonly FirebaseService $firebase)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $updated = 0;

        foreach ($this->firebase->firestore()->collection('landmarks')->documents() as $document) {
            if (! $document->exists()) {
                continue;
            }

            $data = $document->data();
            if (! array_key_exists('visitCount', $data)) {
                continue;
            }

            $updates = [];

            if (! array_key_exists('visit_count', $data)) {
                $updates[] = [
                    'path' => 'visit_count',
                    'value' => $data['visitCount'],
                ];
            }

            $updates[] = [
                'path' => 'visitCount',
                'value' => FieldValue::deleteField(),
            ];

            if (! $this->option('dry-run')) {
                $document->reference()->update($updates);
            }

            $this->line(($this->option('dry-run') ? 'Would clean' : 'Cleaned').": {$document->id()}");
            $updated++;
        }

        $action = $this->option('dry-run') ? 'would be updated' : 'updated';
        $this->info("Cleanup complete. {$updated} landmark documents {$action}.");

        return self::SUCCESS;
    }
}
