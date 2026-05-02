<?php

namespace App\Services;

final class CuratorAccessibleLandmarks
{
    /**
     * All landmark document IDs this curator may work with: same Landmark Manager portfolio
     * (shared manager_uid). Falls back to the primary ID only when no manager is set.
     */
    public static function resolveIds(FirebaseService $firebase, string $primaryLandmarkId): array
    {
        $primaryLandmarkId = trim($primaryLandmarkId);
        if ($primaryLandmarkId === '') {
            return [];
        }

        $fs = $firebase->firestore();
        $doc = $fs->collection('landmarks')->document($primaryLandmarkId)->snapshot();
        if (! $doc->exists()) {
            return [$primaryLandmarkId];
        }

        $managerUid = trim((string) ($doc->data()['manager_uid'] ?? ''));
        if ($managerUid === '') {
            return [$primaryLandmarkId];
        }

        $ids = [];
        foreach ($fs->collection('landmarks')->where('manager_uid', '==', $managerUid)->documents() as $snap) {
            if ($snap->exists()) {
                $ids[] = $snap->id();
            }
        }

        $ids = array_values(array_unique($ids));

        return $ids !== [] ? $ids : [$primaryLandmarkId];
    }
}
