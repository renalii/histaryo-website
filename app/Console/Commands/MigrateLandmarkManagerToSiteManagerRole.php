<?php

namespace App\Console\Commands;

use App\Services\FirebaseService;
use Illuminate\Console\Command;

class MigrateLandmarkManagerToSiteManagerRole extends Command
{
    protected $signature = 'firestore:migrate-site-manager-role';

    protected $description = 'Rename user role landmark_manager to site_manager in Firestore and Firebase Auth claims';

    public function __construct(private readonly FirebaseService $firebase)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $firestore = $this->firebase->firestore();
        $auth = $this->firebase->getAuth();
        $usersRef = $firestore->collection('users');
        $updated = 0;

        foreach ($usersRef->documents() as $doc) {
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

            $usersRef->document($uid)->set([
                'role' => 'site_manager',
                'updated_at' => $now,
            ], ['merge' => true]);

            $auth->setCustomUserClaims($uid, ['role' => 'site_manager']);

            $email = (string) ($data['email'] ?? $uid);
            $this->info("Updated user {$email} ({$uid})");
            $updated++;
        }

        $this->info("Migration complete. Updated {$updated} user(s).");

        return self::SUCCESS;
    }
}
