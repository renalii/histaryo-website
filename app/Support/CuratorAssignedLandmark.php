<?php

namespace App\Support;

use Illuminate\Support\Facades\Session;

final class CuratorAssignedLandmark
{
    public static function id(): ?string
    {
        $id = Session::get('assigned_landmark_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    /** @return list<string> Landmark IDs visible in curator listings (assigned landmark from session). */
    public static function browseableIds(): array
    {
        $ids = Session::get('browseable_landmark_ids');
        if (is_array($ids) && $ids !== []) {
            $out = [];
            foreach ($ids as $id) {
                $id = trim((string) $id);
                if ($id !== '') {
                    $out[] = $id;
                }
            }

            return array_values(array_unique($out));
        }

        $one = self::id();

        return $one !== null ? [$one] : [];
    }

    /** @return list<string> Landmarks this curator may edit, add trivia/QR for, etc. */
    public static function writableIds(): array
    {
        $ids = Session::get('writable_landmark_ids');
        if (is_array($ids) && $ids !== []) {
            $out = [];
            foreach ($ids as $id) {
                $id = trim((string) $id);
                if ($id !== '') {
                    $out[] = $id;
                }
            }

            return array_values(array_unique($out));
        }

        $one = self::id();

        return $one !== null ? [$one] : [];
    }

    public static function canAccess(string $landmarkDocId): bool
    {
        return in_array($landmarkDocId, self::writableIds(), true);
    }

    /** Curator may change landmark-linked data only for landmarks they may write (assigned by default). */
    public static function assertMatches(string $landmarkDocId): void
    {
        if (in_array($landmarkDocId, self::writableIds(), true)) {
            return;
        }

        abort(403);
    }
}
