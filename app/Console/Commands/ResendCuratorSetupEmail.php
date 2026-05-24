<?php

namespace App\Console\Commands;

use App\Services\CuratorWelcomeMailer;
use App\Services\FirebaseService;
use App\Services\SiteManagerLandmarks;
use App\Support\FirestoreBool;
use Illuminate\Console\Command;

class ResendCuratorSetupEmail extends Command
{
    protected $signature = 'histaryo:resend-curator-setup {email : Curator email address}';

    protected $description = 'Resend the curator welcome email with a fresh password-setup link';

    public function handle(
        FirebaseService $firebase,
        SiteManagerLandmarks $landmarks,
        CuratorWelcomeMailer $mailer,
    ): int {
        $email = strtolower(trim((string) $this->argument('email')));
        if ($email === '') {
            $this->error('Email is required.');

            return self::FAILURE;
        }

        $users = $firebase->firestore()->collection('users')
            ->where('email', '=', $email)
            ->limit(1)
            ->documents();

        $doc = null;
        foreach ($users as $snapshot) {
            $doc = $snapshot;
            break;
        }

        if ($doc === null || ! $doc->exists()) {
            $this->error("No user found with email {$email}.");

            return self::FAILURE;
        }

        if (strtolower((string) ($doc['role'] ?? '')) !== 'curator') {
            $this->error('That account is not a curator.');

            return self::FAILURE;
        }

        if (! FirestoreBool::isTrue($doc['must_change_password'] ?? false)) {
            $this->error('This curator has already set their password. They can use the normal login page.');

            return self::FAILURE;
        }

        $uid = $doc->id();
        $landmarkId = trim((string) ($doc['assigned_landmark_id'] ?? ''));
        $landmarkLabel = $landmarkId !== ''
            ? ($landmarks->landmarkLabel($landmarkId) ?? $landmarkId)
            : 'Assigned landmark';

        $result = $mailer->send(
            firstName: (string) ($doc['first_name'] ?? 'Curator'),
            lastName: (string) ($doc['last_name'] ?? ''),
            email: $email,
            plainPassword: '(use the temporary password from your original welcome email)',
            landmarkLabel: $landmarkLabel,
            uid: $uid,
        );

        $this->info('Public base URL: '.config('app.public_url'));

        if ($result['sent']) {
            $this->info("Sent a fresh setup link to {$email}.");

            return self::SUCCESS;
        }

        $this->warn('Mail failed: '.($result['error'] ?? 'unknown error'));
        if ($result['preview_path']) {
            $this->line('Preview: storage/app/'.$result['preview_path']);
        }

        return self::FAILURE;
    }
}
