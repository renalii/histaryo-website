<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

final class SiteManagerReadModel
{
    private const TTL_SECONDS = 300;

    public function __construct(protected FirebaseService $firebase) {}

    /** @return list<array<string, mixed>> */
    public function landmarks(string $managerUid): array
    {
        $managerUid = trim($managerUid);
        if ($managerUid === '') {
            return [];
        }

        return Cache::remember($this->key($managerUid, 'landmarks'), self::TTL_SECONDS, function () use ($managerUid) {
            $records = [];

            foreach (['manager_uid', 'managerUid'] as $field) {
                $documents = $this->firebase->firestore()
                    ->collection('landmarks')
                    ->where($field, '==', $managerUid)
                    ->documents();

                foreach ($documents as $document) {
                    if ($document->exists()) {
                        $records[$document->id()] = ['id' => $document->id()] + $document->data();
                    }
                }
            }

            return array_values($records);
        });
    }

    /** @return list<array<string, mixed>> */
    public function curators(string $managerUid): array
    {
        $managerUid = trim($managerUid);
        if ($managerUid === '') {
            return [];
        }

        return Cache::remember($this->key($managerUid, 'curators'), self::TTL_SECONDS, function () use ($managerUid) {
            $landmarkSet = array_fill_keys(array_column($this->landmarks($managerUid), 'id'), true);
            if ($landmarkSet === []) {
                return [];
            }

            $collection = $this->firebase->userCollection('curator');
            $documents = count($landmarkSet) <= 30
                ? $collection->where('assigned_landmark_id', 'in', array_keys($landmarkSet))->documents()
                : $collection->documents();
            $records = [];

            foreach ($documents as $document) {
                if (! $document->exists()) {
                    continue;
                }
                $data = $document->data();
                if (isset($landmarkSet[trim((string) ($data['assigned_landmark_id'] ?? ''))])) {
                    $records[] = ['uid' => $document->id()] + $data;
                }
            }

            return $records;
        });
    }

    public function dashboardKey(string $managerUid): string
    {
        return $this->key($managerUid, 'dashboard');
    }

    public function forget(string $managerUid): void
    {
        $managerUid = trim($managerUid);
        if ($managerUid === '') {
            return;
        }

        foreach (['landmarks', 'curators', 'dashboard'] as $suffix) {
            Cache::forget($this->key($managerUid, $suffix));
        }
    }

    private function key(string $managerUid, string $suffix): string
    {
        return 'site-manager:'.$managerUid.':'.$suffix.':v15';
    }
}
