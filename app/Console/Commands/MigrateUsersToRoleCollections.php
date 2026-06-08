<?php

namespace App\Console\Commands;

use App\Services\FirebaseService;
use Illuminate\Console\Command;

class MigrateUsersToRoleCollections extends Command
{
    protected $signature = 'firestore:migrate-users-to-role-collections {--dry-run : Show planned moves without writing}';

    protected $description = 'Move user profiles into users/{role}/users/{uid} subcollections';

    public function __construct(private readonly FirebaseService $firebase)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $moved = 0;
        $skipped = 0;

        foreach ($this->profileSources() as $source) {
            $doc = $source['doc'];
            if (! $doc->exists()) {
                continue;
            }

            $uid = $doc->id();
            $data = $doc->data();
            $role = $this->firebase->normalizeUserRole($data['role'] ?? $source['role']);
            $targetCollection = $this->firebase->userCollectionPath($role);
            $targetRef = $this->firebase->userDocument($uid, $role);

            $this->line("{$uid}: {$source['label']} -> {$targetCollection}");

            if ($dryRun) {
                $skipped++;
                continue;
            }

            $targetRef->set(array_merge($data, ['role' => $role]), ['merge' => true]);
            $doc->reference()->delete();
            $moved++;
        }

        if ($dryRun) {
            $this->info("Dry run complete. {$skipped} user profile(s) would be moved.");

            return self::SUCCESS;
        }

        $this->info("Migration complete. Moved {$moved} user profile(s) into users/{role}/users/{uid}.");

        return self::SUCCESS;
    }

    private function profileSources(): array
    {
        $sources = [];
        $roleDocumentIds = array_flip(FirebaseService::USER_COLLECTIONS);

        foreach ($this->firebase->firestore()->collection('users')->documents() as $doc) {
            if (! $doc->exists()) {
                continue;
            }

            if (isset($roleDocumentIds[$doc->id()])) {
                continue;
            }

            $data = $doc->data();
            if (array_key_exists('role', $data) || array_key_exists('email', $data)) {
                $sources[] = [
                    'doc' => $doc,
                    'label' => 'users',
                    'role' => $data['role'] ?? null,
                ];
            }
        }

        foreach (FirebaseService::USER_COLLECTIONS as $role => $collectionName) {
            foreach ($this->firebase->firestore()->collection($collectionName)->documents() as $doc) {
                if ($doc->exists()) {
                    $sources[] = [
                        'doc' => $doc,
                        'label' => $collectionName,
                        'role' => $role,
                    ];
                }
            }

            foreach ($this->firebase->firestore()
                ->collection('users')
                ->document($collectionName)
                ->collection('profiles')
                ->documents() as $doc) {
                if ($doc->exists()) {
                    $sources[] = [
                        'doc' => $doc,
                        'label' => 'users/'.$collectionName.'/profiles',
                        'role' => $role,
                    ];
                }
            }

            foreach ($this->firebase->firestore()
                ->collection('user_roles')
                ->document($collectionName)
                ->collection('users')
                ->documents() as $doc) {
                if ($doc->exists()) {
                    $sources[] = [
                        'doc' => $doc,
                        'label' => 'user_roles/'.$collectionName.'/users',
                        'role' => $role,
                    ];
                }
            }
        }

        return $sources;
    }

}
