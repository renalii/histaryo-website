<?php

namespace App\Services;

use App\Support\LandmarkActivation;

final class SiteManagerLandmarks
{
    public function __construct(protected FirebaseService $firebase) {}

    /**
     * Active, unassigned landmarks the signed-in Site Manager may assign a new curator to.
     *
     * @return list<array{id: string, name: string, landmarkcode: string}>
     */
    public function assignableLandmarks(string $managerUid): array
    {
        return $this->curatorAssignmentLandmarks($managerUid)['assignable'];
    }

    /**
     * @return array{
     *     assignable: list<array{id: string, name: string, landmarkcode: string}>,
     *     all_active_assigned: bool
     * }
     */
    public function curatorAssignmentLandmarks(string $managerUid): array
    {
        $portfolio = $this->portfolioActiveLandmarks($managerUid);
        if ($portfolio === []) {
            return [
                'assignable' => [],
                'all_active_assigned' => false,
            ];
        }

        $takenIds = $this->takenLandmarkIds(array_column($portfolio, 'id'));
        $assignable = array_values(array_filter(
            $portfolio,
            fn (array $landmark): bool => ! isset($takenIds[$landmark['id']])
        ));

        return [
            'assignable' => $assignable,
            'all_active_assigned' => $assignable === [],
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
        foreach ($this->firebase->firestore()->collection('landmarks')->documents() as $doc) {
            if (! $doc->exists()) {
                continue;
            }

            $data = $doc->data();
            $docManager = trim((string) ($data['manager_uid'] ?? $data['managerUid'] ?? ''));
            if ($docManager === '' || $docManager !== $managerUid) {
                continue;
            }

            $activation = strtolower((string) ($data['activation_status'] ?? 'active'));
            if (! LandmarkActivation::isBrowsable($activation)) {
                continue;
            }

            $options[] = [
                'id' => $doc->id(),
                'name' => trim((string) ($data['name'] ?? 'Unnamed landmark')),
                'landmarkcode' => strtoupper(trim((string) ($data['landmarkcode'] ?? ''))),
            ];
        }

        usort($options, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $options;
    }

    /**
     * Landmark IDs in $landmarkIds that already have a curator assigned.
     *
     * @param  list<string>  $landmarkIds
     * @return array<string, true>
     */
    private function takenLandmarkIds(array $landmarkIds): array
    {
        $inPortfolio = array_flip(array_filter(array_map('trim', $landmarkIds)));
        if ($inPortfolio === []) {
            return [];
        }

        $taken = [];
        foreach ($this->firebase->firestore()->collection('users')->documents() as $doc) {
            if (! $doc->exists()) {
                continue;
            }

            $data = $doc->data();
            if (strtolower((string) ($data['role'] ?? '')) !== 'curator') {
                continue;
            }

            $landmarkId = trim((string) ($data['assigned_landmark_id'] ?? ''));
            if ($landmarkId === '' || ! isset($inPortfolio[$landmarkId])) {
                continue;
            }

            $taken[$landmarkId] = true;
        }

        return $taken;
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
