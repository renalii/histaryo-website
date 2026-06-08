<?php

namespace App\Console\Commands;

use App\Services\FirebaseService;
use Illuminate\Console\Command;

class MigrateSiteManagerRole extends Command
{
    protected $signature = 'firestore:migrate-site-manager-role';

    protected $description = 'Rename user role landmark_manager to site_manager in Firestore and Firebase Auth claims';

    public function __construct(private readonly FirebaseService $firebase)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $auth = $this->firebase->getAuth();
        $updated = 0;

        foreach (array_keys(FirebaseService::USER_COLLECTIONS) as $roleKey) {
            $profilesRef = $this->firebase->userCollection($roleKey);

            foreach ($profilesRef->documents() as $doc) {
                if (! $doc->exists()) {
                    continue;
                }

                $data = $doc->data();
                $role = strtolower(trim((string) ($data['role'] ?? '')));

                if ($role !== 'landmark_manager') {
                    continue;
                }

                $uid = $doc->id();
                $now = now()->toDateTimeString();
                $targetRef = $this->firebase->userDocument($uid, 'site_manager');
                $targetRef->set(array_merge($data, [
                    'role' => 'site_manager',
                    'updated_at' => $now,
                ]), ['merge' => true]);

                if ($roleKey !== 'site_manager') {
                    $doc->reference()->delete();
                }

                $auth->setCustomUserClaims($uid, ['role' => 'site_manager']);

                $email = (string) ($data['email'] ?? $uid);
                $this->info("Updated user {$email} ({$uid})");
                $updated++;
            }
        }

        $this->info("Migration complete. Updated {$updated} user(s).");

        return self::SUCCESS;
    }
}
