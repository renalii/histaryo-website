<?php

namespace App\Console\Commands;

use App\Services\CuratorWelcomeMailer;
use Illuminate\Console\Command;

class PreviewCuratorWelcomeEmail extends Command
{
    protected $signature = 'histaryo:preview-curator-email {email : Curator email address to send the sample to}';

    protected $description = 'Send a sample curator welcome email (tests Gmail SMTP) and save an HTML preview';

    public function handle(CuratorWelcomeMailer $mailer): int
    {
        if (! filled(config('mail.mailers.smtp.username')) || ! filled(config('mail.mailers.smtp.password'))) {
            $this->error('Gmail is not configured. Set MAIL_USERNAME and MAIL_PASSWORD (App Password) in .env, then run: php artisan config:clear');

            return self::FAILURE;
        }

        $email = strtolower(trim((string) $this->argument('email')));

        $result = $mailer->send(
            firstName: 'Jaime',
            lastName: 'Lapiz',
            email: $email,
            plainPassword: 'TempPass123',
            landmarkLabel: 'Sample Landmark',
            uid: 'preview-uid-not-real',
        );

        $previewFull = storage_path('app/'.$result['preview_path']);
        if (! is_file($previewFull)) {
            $previewFull = storage_path('app/private/'.$result['preview_path']);
        }

        $this->info('Public base URL for links: '.config('app.public_url'));
        $this->info('HTML preview: '.$previewFull);

        if ($result['sent']) {
            $this->info("Sent to {$email}. Check inbox and Spam.");
        } else {
            $this->warn('Mail error: '.$result['error']);
            $this->line('Common fixes: use an App Password (not your normal Gmail password), MAIL_FROM_ADDRESS = MAIL_USERNAME, 2-Step Verification ON.');
            $this->line('Preview file: '.$previewFull);
        }

        return self::SUCCESS;
    }
}
