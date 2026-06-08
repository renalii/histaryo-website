<?php

namespace App\Services;

use App\Support\LandmarkActivation;
use App\Support\LandmarkVisibility;

final class ActiveLandmarksCatalog
{
    /**
     * Landmark document IDs curators can browse (active / legacy; excludes pending activation).
     *
     * @return list<string>
     */
    public static function documentIds(FirebaseService $firebase): array
    {
        $ids = [];
        foreach ($firebase->firestore()->collection('landmarks')->documents() as $snap) {
            if (! $snap->exists()) {
                continue;
            }
            $d = $snap->data();
            $activation = strtolower((string) ($d['activation_status'] ?? 'active'));
            if (! LandmarkActivation::isBrowsable($activation)) {
                continue;
            }
            if (! LandmarkVisibility::isPublic($d['visibility'] ?? '', $activation)) {
                continue;
            }
            $ids[] = $snap->id();
        }

        return array_values(array_unique($ids));
    }
}
