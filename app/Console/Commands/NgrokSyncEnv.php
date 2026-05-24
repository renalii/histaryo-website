<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class NgrokSyncEnv extends Command
{
    protected $signature = 'ngrok:sync';

    protected $description = 'Set QR_PUBLIC_BASE_URL from the local ngrok agent (http://127.0.0.1:4040); leave APP_URL local';

    public function handle(): int
    {
        $ctx = stream_context_create(['http' => ['timeout' => 5]]);
        $json = @file_get_contents('http://127.0.0.1:4040/api/tunnels', false, $ctx);
        if ($json === false) {
            $this->error('Could not reach ngrok at http://127.0.0.1:4040. Start it first: ngrok http 127.0.0.1:8000');

            return self::FAILURE;
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($json, true) ?? [];
        $httpsUrl = null;
        foreach ($data['tunnels'] ?? [] as $tunnel) {
            if (! is_array($tunnel)) {
                continue;
            }
            $url = (string) ($tunnel['public_url'] ?? '');
            if (str_starts_with($url, 'https://')) {
                $httpsUrl = rtrim($url, '/');
                break;
            }
        }

        if ($httpsUrl === null || $httpsUrl === '') {
            $this->error('No HTTPS tunnel found. Open http://127.0.0.1:4040 and confirm ngrok is running.');

            return self::FAILURE;
        }

        $path = base_path('.env');
        if (! File::exists($path)) {
            $this->error('.env not found at project root.');

            return self::FAILURE;
        }

        $text = File::get($path);
        if (! preg_match('/^QR_PUBLIC_BASE_URL=/m', $text)) {
            $this->error('.env must contain a line: QR_PUBLIC_BASE_URL=');

            return self::FAILURE;
        }

        $text = preg_replace('/^QR_PUBLIC_BASE_URL=.*$/m', 'QR_PUBLIC_BASE_URL='.$httpsUrl, $text) ?? $text;

        File::put($path, $text);

        $this->info("QR_PUBLIC_BASE_URL set to {$httpsUrl} (APP_URL and email links unchanged).");
        $this->call('config:clear');
        $this->line('Re-open Curators → QR → Preview QR so PNGs match this URL.');

        return self::SUCCESS;
    }
}
