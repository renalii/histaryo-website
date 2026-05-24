<?php

namespace App\Services;

use App\Mail\CuratorAccountCreatedMail;
use App\Support\PublicAppUrl;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

class CuratorWelcomeMailer
{
    /**
     * @return array{sent: bool, error: ?string, preview_path: ?string}
     */
    public function send(
        string $firstName,
        string $lastName,
        string $email,
        string $plainPassword,
        string $landmarkLabel,
        string $uid,
    ): array {
        $changePasswordUrl = PublicAppUrl::temporarySignedRoute(
            'curators.setup-password',
            now()->addDays(7),
            ['uid' => $uid]
        );

        $mailable = new CuratorAccountCreatedMail(
            firstName: $firstName,
            lastName: $lastName,
            email: $email,
            plainPassword: $plainPassword,
            landmarkLabel: $landmarkLabel,
            changePasswordUrl: $changePasswordUrl,
        );

        $previewDir = storage_path('app/mail-previews');
        File::ensureDirectoryExists($previewDir);
        $previewPath = 'mail-previews/latest-curator-welcome.html';
        File::put(storage_path('app/'.$previewPath), $mailable->render());

        try {
            Mail::send($mailable);

            return [
                'sent' => true,
                'error' => null,
                'preview_path' => $previewPath,
            ];
        } catch (\Throwable $e) {
            report($e);

            return [
                'sent' => false,
                'error' => $e->getMessage(),
                'preview_path' => $previewPath,
            ];
        }
    }
}
