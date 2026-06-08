<?php

namespace App\Console\Commands;

use App\Services\FirebaseService;
use Illuminate\Console\Command;

class MigrateVisitorsCollection extends Command
{
    protected $signature = 'firestore:migrate-visitors-collection {--dry-run : Show planned moves without writing}';

    protected $description = 'Move visitor profiles from legacy Firestore paths to users/visitors/users/{uid}';

    public function __construct(private readonly FirebaseService $firebase)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $moved = 0;
        $skipped = 0;
        $targetCollection = $this->firebase->userCollectionPath('visitor');

        foreach ($this->visitorProfileSources() as $source) {
            $doc = $source['doc'];
            if (! $doc->exists()) {
                continue;
            }

            $uid = $doc->id();
            $data = $doc->data();
            $targetRef = $this->firebase->userDocument($uid, 'visitor');

            $this->line("{$uid}: {$source['label']} -> {$targetCollection}");

            if ($dryRun) {
                $skipped++;
                continue;
            }

            $this->copyDocumentTree($doc->reference(), $targetRef, array_merge($data, ['role' => 'visitor']));
            $this->deleteDocumentTree($doc->reference());
            $moved++;
        }

        if ($dryRun) {
            $this->info("Dry run complete. {$skipped} visitor profile(s) would be moved.");

            return self::SUCCESS;
        }

        $this->info("Migration complete. Moved {$moved} visitor profile(s) to {$targetCollection}/{uid}.");

        return self::SUCCESS;
    }

    private function visitorProfileSources(): array
    {
        $sources = [];
        $reservedUserDocumentIds = array_merge(
            array_values(FirebaseService::USER_COLLECTIONS),
            ['visitor', 'visitors']
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

        $legacyVisitorRoleDoc = 'visitor';
        foreach ($this->firebase->firestore()
            ->collection('users')
            ->document($legacyVisitorRoleDoc)
            ->collection(FirebaseService::USER_PROFILE_SUBCOLLECTION)
            ->documents() as $doc) {
            if ($doc->exists()) {
                $sources[] = [
                    'doc' => $doc,
                    'label' => 'users/'.$legacyVisitorRoleDoc.'/'.FirebaseService::USER_PROFILE_SUBCOLLECTION,
                ];
            }
        }

        return $sources;
    }

    private function copyDocumentTree($sourceRef, $targetRef, ?array $sourceData = null): void
    {
        $data = $sourceData ?? $sourceRef->snapshot()->data();
        $targetRef->set($data, ['merge' => true]);

        foreach ($sourceRef->collections() as $sourceCollection) {
            $targetCollection = $targetRef->collection($sourceCollection->id());

            foreach ($sourceCollection->documents() as $childDoc) {
                if (! $childDoc->exists()) {
                    continue;
                }

                $this->copyDocumentTree(
                    $childDoc->reference(),
                    $targetCollection->document($childDoc->id()),
                    $childDoc->data()
                );
            }
        }
    }

    private function deleteDocumentTree($documentRef): void
    {
        foreach ($documentRef->collections() as $collection) {
            foreach ($collection->documents() as $childDoc) {
                if ($childDoc->exists()) {
                    $this->deleteDocumentTree($childDoc->reference());
                }
            }
        }

        $documentRef->delete();
    }
}
