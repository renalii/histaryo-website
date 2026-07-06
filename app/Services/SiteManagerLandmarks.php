<?php

namespace App\Services;

use App\Support\LandmarkActivation;

final class SiteManagerLandmarks
{
    public function __construct(
        protected FirebaseService $firebase,
        protected SiteManagerReadModel $siteManagerReadModel
    ) {}

    /** @return list<array{id: string, name: string, landmarkcode: string}> */
    public function assignableLandmarks(string $managerUid): array
    {
        return $this->curatorAssignmentLandmarks($managerUid)['assignable'];
    }

    /**
     * @return array{
     *     assignable: list<array{id: string, name: string, landmarkcode: string}>
     * }
     */
    public function curatorAssignmentLandmarks(string $managerUid): array
    {
        $portfolio = $this->portfolioActiveLandmarks($managerUid);

        return [
            'assignable' => $portfolio,
        ];
    }

    /**
     * @return list<array{id: string, name: string, landmarkcode: string}>
     */
    private function portfolioActiveLandmarks(string $managerUid): array
    {
        $managerUid = trim($managerUid);
        if ($managerUid === '') {
            return [];
        }

        $options = [];
        foreach ($this->siteManagerReadModel->landmarks($managerUid) as $data) {
            $activation = strtolower((string) ($data['activation_status'] ?? 'active'));
            if (! LandmarkActivation::isBrowsable($activation)) {
                continue;
            }
            $options[] = [
                'id' => (string) $data['id'],
                'name' => trim((string) ($data['name'] ?? 'Unnamed landmark')),
                'landmarkcode' => strtoupper(trim((string) ($data['landmarkcode'] ?? ''))),
            ];
        }

        usort($options, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $options;
    }

    public function managerMayAssignLandmark(string $managerUid, string $landmarkId): bool
    {
        $landmarkId = trim($landmarkId);
        if ($landmarkId === '') {
            return false;
        }

        foreach ($this->assignableLandmarks($managerUid) as $landmark) {
            if ($landmark['id'] === $landmarkId) {
                return true;
            }
        }

        return false;
    }

    public function landmarkLabel(string $landmarkId): ?string
    {
        $landmarkId = trim($landmarkId);
        if ($landmarkId === '') {
            return null;
        }

        $snap = $this->firebase->firestore()->collection('landmarks')->document($landmarkId)->snapshot();
        if (! $snap->exists()) {
            return null;
        }

        $data = $snap->data();
        $name = trim((string) ($data['name'] ?? ''));
        $code = strtoupper(trim((string) ($data['landmarkcode'] ?? '')));

        if ($name === '') {
            return $code !== '' ? $code : 'Landmark';
        }

        return $code !== '' ? $name.' ('.$code.')' : $name;
    }
}
