<?php

namespace App\Services;

use App\Support\LandmarkActivation;

final class CuratorAccessibleLandmarks
{
    /**
     * All landmark document IDs this curator may work with: same Site Manager portfolio
     * (shared manager_uid / managerUid). Falls back to the primary ID only when no manager is set.
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

        $d = $doc->data();
        $managerUid = trim((string) ($d['manager_uid'] ?? $d['managerUid'] ?? ''));
        if ($managerUid === '') {
            return [$primaryLandmarkId];
        }

        $ids = [];
        foreach (['manager_uid', 'managerUid'] as $field) {
            foreach ($fs->collection('landmarks')->where($field, '==', $managerUid)->documents() as $snap) {
                if (! $snap->exists()) {
                    continue;
                }
                $activation = strtolower((string) ($snap->data()['activation_status'] ?? 'active'));
                if (! LandmarkActivation::isBrowsable($activation)) {
                    continue;
                }
                $ids[] = $snap->id();
            }
        }

        $ids = array_values(array_unique($ids));

        return $ids !== [] ? $ids : [$primaryLandmarkId];
    }
}
