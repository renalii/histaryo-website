<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FirebaseService;

class MigrateDisplayNameToName extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firestore:migrate-displayname';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rename display_name field to name for all users in Firestore';

    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        parent::__construct();
        $this->firebase = $firebase;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $firestore = $this->firebase->firestore();
        $usersRef = $firestore->collection('users');
        $documents = $usersRef->documents();

        $updated = 0;

        foreach ($documents as $doc) {
            if ($doc->exists()) {
                $data = $doc->data();

                if (isset($data['display_name'])) {
                    $usersRef->document($doc->id())->update([
                        ['path' => 'name', 'value' => $data['display_name']],
                    ]);

                    $usersRef->document($doc->id())->update([
                        ['path' => 'display_name', 'value' => null],
                    ]);

                    $this->info("Updated user: {$doc->id()} ({$data['display_name']})");
                    $updated++;
                }
            }
        }

        $this->info("✅ Migration complete! Updated {$updated} users.");
        return 0;
    }
}
