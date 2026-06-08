<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class LandmarkVideo
{
    /** @param array<string, mixed> $landmark */
    public static function url(array $landmark): string
    {
        if (empty($landmark['video_is_upload'])) {
            return '';
        }

        $path = trim((string) ($landmark['video_path'] ?? ''));
        if ($path !== '') {
            return asset(Storage::url($path));
        }

        $url = trim((string) ($landmark['video_url'] ?? ''));

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }
}
