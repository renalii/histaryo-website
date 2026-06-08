<?php

namespace App\Console\Commands;

use App\Services\FirebaseService;
use Illuminate\Console\Command;

class MigrateVisitorsCollection extends Command
{
    protected $signature = 'firestore:migrate-visitors-collection {--dry-run : Show planned moves without writing}';

    protected $description = 'Move visitor profiles from legacy Firestore paths to users/visitor/users/{uid}';

    public function __construct(private readonly FirebaseService $firebase)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $moved = 0;
        $skipped = 0;
        foreach ($this->visitorProfileSources() as $source) {
            $doc = $source['doc'];
            if (! $doc->exists()) {
                continue;
            }

            $uid = $doc->id();
            $data = $doc->data();
            $targetRef = $this->firebase->userDocument($uid, 'visitor');

            $this->line("{$uid}: {$source['label']} -> users/visitor/users");

            if ($dryRun) {
                $skipped++;
                continue;
            }

            $targetRef->set(array_merge($data, ['role' => 'visitor']), ['merge' => true]);
            $doc->reference()->delete();
            $moved++;
        }

        if ($dryRun) {
            $this->info("Dry run complete. {$skipped} visitor profile(s) would be moved.");

            return self::SUCCESS;
        }

        $this->info("Migration complete. Moved {$moved} visitor profile(s) to users/visitor/users/{uid}.");

        return self::SUCCESS;
    }

    private function visitorProfileSources(): array
    {
        $sources = [];
        $reservedUserDocumentIds = array_merge(
            array_values(FirebaseService::USER_COLLECTIONS),
            ['visitors']
        );

        foreach ($this->firebase->firestore()->collection('users')->documents() as $doc) {
            if (! $doc->exists() || in_array($doc->id(), $reservedUserDocumentIds, true)) {
                continue;
            }

            $data = $doc->data();
            $role = strtolower(trim((string) ($data['role'] ?? '')));
            if ($role !== 'visitor') {
                continue;
            }

            $sources[] = [
                'doc' => $doc,
                'label' => 'users',
            ];
        }

        foreach ($this->firebase->firestore()
            ->collection('users')
            ->document('visitors')
            ->collection(FirebaseService::USER_PROFILE_SUBCOLLECTION)
            ->documents() as $doc) {
            if ($doc->exists()) {
                $sources[] = [
                    'doc' => $doc,
                    'label' => 'users/visitors/users',
                ];
            }
        }

        return $sources;
    }
}
