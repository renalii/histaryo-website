<?php

namespace App\Services;

use App\Support\LandmarkActivation;

/**
 * Landmark document IDs a curator may view in listings, maps, and dashboard stats.
 * Scoped to their Firestore users profile ({@see assigned_landmark_id}), not the full catalog.
 */
final class CuratorBrowseableLandmarks
{
    /**
     * @return list<string>
     */
    public static function resolveIds(FirebaseService $firebase, string $assignedLandmarkId): array
    {
        $assignedLandmarkId = trim($assignedLandmarkId);
        if ($assignedLandmarkId === '') {
            return [];
        }

        $snap = $firebase->firestore()->collection('landmarks')->document($assignedLandmarkId)->snapshot();
        if (! $snap->exists()) {
            return [$assignedLandmarkId];
        }

        $activation = strtolower((string) ($snap->data()['activation_status'] ?? 'active'));
        if (! LandmarkActivation::isBrowsable($activation)) {
            return [];
        }

        return [$assignedLandmarkId];
    }

    /**
     * Read assignment from users/{uid} then resolve browseable landmark ids.
     *
     * @return list<string>
     */
    public static function resolveIdsForCurator(FirebaseService $firebase, string $curatorUid): array
    {
        $curatorUid = trim($curatorUid);
        if ($curatorUid === '') {
            return [];
        }

        $userDoc = $firebase->firestore()->collection('users')->document($curatorUid)->snapshot();
        if (! $userDoc->exists()) {
            return [];
        }

        $assigned = trim((string) ($userDoc['assigned_landmark_id'] ?? ''));

        return self::resolveIds($firebase, $assigned);
    }
}
