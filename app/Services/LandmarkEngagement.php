<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class LandmarkEngagement
{
    public function __construct(protected FirebaseService $firebase) {}

    public function record(Request $request, string $landmarkId, string $eventType, array $extra = []): void
    {
        $landmarkId = trim($landmarkId);
        $eventType = strtolower(trim($eventType));
        if ($landmarkId === '' || ! in_array($eventType, ['qr_scan', 'landmark_view', 'quiz_attempt'], true)) {
            return;
        }

        $visitorKey = (string) $request->session()->get('engagement_visitor_key', '');
        if ($visitorKey === '') {
            $visitorKey = hash('sha256', implode('|', [
                (string) $request->ip(),
                (string) $request->userAgent(),
                (string) $request->session()->getId(),
            ]));
            $request->session()->put('engagement_visitor_key', $visitorKey);
        }

        Log::info('Landmark engagement skipped; Firestore landmark activity collection is disabled.', [
            'landmark_id' => $landmarkId,
            'event_type' => $eventType,
        ]);
    }

    /**
     * @return array{
     *   qr_scans:int,landmark_views:int,quiz_attempts:int,unique_visitors:int,
     *   last_activity:?string,daily_labels:list<string>,daily_values:list<int>
     * }
     */
    public function summary(string $landmarkId): array
    {
        return $this->summaryForLandmarks([$landmarkId]);
    }

    /**
     * @param list<string> $landmarkIds
     * @return array{
     *   qr_scans:int,landmark_views:int,quiz_attempts:int,unique_visitors:int,
     *   last_activity:?string,daily_labels:list<string>,daily_values:list<int>
     * }
     */
    public function summaryForLandmarks(array $landmarkIds): array
    {
        return $this->analyticsForLandmarks($landmarkIds)['totals'];
    }

    /**
     * @param list<string> $landmarkIds
     * @return array{
     *   totals:array{
     *     qr_scans:int,landmark_views:int,quiz_attempts:int,unique_visitors:int,
     *     last_activity:?string,daily_labels:list<string>,daily_values:list<int>
     *   },
     *   records:list<array<string,mixed>>
     * }
     */
    public function analyticsForLandmarks(array $landmarkIds): array
    {
        $summary = [
            'qr_scans' => 0,
            'landmark_views' => 0,
            'quiz_attempts' => 0,
            'unique_visitors' => 0,
            'visitor_users' => 0,
            'last_activity' => null,
            'daily_labels' => [],
            'daily_values' => [],
        ];

        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $key = $day->format('Y-m-d');
            $days[$key] = 0;
            $summary['daily_labels'][] = $day->format('M j');
        }

        $landmarkSet = [];
        foreach ($landmarkIds as $landmarkId) {
            $landmarkId = trim((string) $landmarkId);
            if ($landmarkId !== '') {
                $landmarkSet[$landmarkId] = true;
            }
        }
        if ($landmarkSet === []) {
            $summary['daily_values'] = array_values($days);

            return ['totals' => $summary, 'records' => []];
        }

        $cacheKey = 'landmark-engagement:visitor-visits:v2:'.md5(implode('|', array_keys($landmarkSet)));

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($summary, $days, $landmarkSet): array {
            $start = microtime(true);
            $visitorProfileCount = null;
            $records = $this->visitRecordsForLandmarks($landmarkSet, $visitorProfileCount);
            $visitors = [];
            $visitorUsers = [];
            $last = null;

            foreach ($records as $record) {
                $summary['landmark_views'] += max(1, (int) ($record['visit_count'] ?? 1));

                $visitorKey = trim((string) ($record['visitor_key'] ?? ''));
                if ($visitorKey !== '') {
                    $visitors[$visitorKey] = true;
                    $visitorUsers[$visitorKey] = true;
                }

                $occurredAt = $this->toCarbon($record['occurred_at'] ?? null);
                if ($occurredAt !== null) {
                    if ($last === null || $occurredAt->greaterThan($last)) {
                        $last = $occurredAt;
                    }
                    $dayKey = $occurredAt->format('Y-m-d');
                    if (array_key_exists($dayKey, $days)) {
                        $days[$dayKey] += max(1, (int) ($record['visit_count'] ?? 1));
                    }
                }
            }

            usort($records, function (array $a, array $b): int {
                $aTime = strtotime((string) ($a['occurred_at'] ?? '')) ?: 0;
                $bTime = strtotime((string) ($b['occurred_at'] ?? '')) ?: 0;

                return $bTime <=> $aTime;
            });

            $summary['unique_visitors'] = count($visitors);
            $summary['visitor_users'] = $visitorProfileCount ?? count($visitorUsers);
            $summary['last_activity'] = $last?->toIso8601String();
            $summary['daily_values'] = array_values($days);

            Log::info('Timing Firestore query', [
                'query' => 'visitor_profiles.visits_by_landmark',
                'landmark_count' => count($landmarkSet),
                'records' => count($records),
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
            ]);

            return ['totals' => $summary, 'records' => $records];
        });
    }

    /** @param array<string, true> $landmarkSet @return list<array<string,mixed>> */
    private function visitRecordsForLandmarks(array $landmarkSet, ?int &$visitorProfileCount = null): array
    {
        return $this->visitRecordsFromVisitorProfiles($landmarkSet, $visitorProfileCount);
    }

    /** @param array<string, true> $landmarkSet @return list<array<string,mixed>> */
    private function visitRecordsFromCollectionGroup(array $landmarkSet): array
    {
        try {
            $firestore = $this->firebase->firestore();
            if (! method_exists($firestore, 'collectionGroup')) {
                return [];
            }

            $records = [];
            $seen = [];
            $options = $this->firestoreDashboardOptions();
            foreach (['landmarkId', 'landmark_id'] as $field) {
                foreach (array_chunk(array_keys($landmarkSet), 30) as $chunk) {
                    $query = count($chunk) === 1
                        ? $firestore->collectionGroup('visits')->where($field, '==', $chunk[0])
                        : $firestore->collectionGroup('visits')->where($field, 'in', $chunk);

                    try {
                        foreach ($query->documents($options) as $document) {
                            if (! $document->exists()) {
                                continue;
                            }
                            $key = $document->id().':'.md5(json_encode($document->data()));
                            if (isset($seen[$key])) {
                                continue;
                            }
                            $seen[$key] = true;

                            $record = $this->recordFromVisitData($document->id(), $document->data(), $landmarkSet);
                            if ($record !== null) {
                                $records[] = $record;
                            }
                        }
                    } catch (\Throwable $exception) {
                        Log::warning('Unable to load visitor visits using collection group query.', [
                            'field' => $field,
                            'landmark_count' => count($chunk),
                            'exception' => $exception->getMessage(),
                        ]);
                    }
                }
            }

            return $records;
        } catch (\Throwable $exception) {
            Log::warning('Unable to load visitor visits using collection group query.', [
                'exception' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    /** @return array<string, int|float> */
    private function firestoreDashboardOptions(): array
    {
        return [
            'maxRetries' => 0,
            'requestTimeout' => (float) config('services.firebase.dashboard_query_timeout', 3),
        ];
    }

    /** @param array<string, true> $landmarkSet @return list<array<string,mixed>> */
    private function visitRecordsFromVisitorProfiles(array $landmarkSet, ?int &$visitorProfileCount = null): array
    {
        $start = microtime(true);
        $options = $this->firestoreDashboardOptions();
        $records = [];
        $visitorCount = 0;

        foreach ($this->firebase->userCollection(FirebaseService::VISITOR_ROLE)->documents($options) as $visitorDocument) {
            if (! $visitorDocument->exists()) {
                continue;
            }
            $visitorCount++;

            $visitorData = $visitorDocument->data();
            $visitorKey = trim((string) ($visitorData['uid'] ?? $visitorDocument->id()));
            $visitorName = trim((string) ($visitorData['fullName'] ?? $visitorData['name'] ?? ''));
            if ($visitorName === '') {
                $visitorName = trim((string) ($visitorData['email'] ?? $visitorKey));
            }

            foreach ($visitorDocument->reference()->collection('visits')->documents($options) as $visitDocument) {
                if (! $visitDocument->exists()) {
                    continue;
                }

                $record = $this->recordFromVisitDocument(
                    $visitDocument,
                    $visitorKey,
                    $visitorName,
                    $visitorData['visitCount'] ?? null,
                    $landmarkSet
                );
                if ($record !== null) {
                    $records[] = $record;
                }
            }
        }

        Log::info('Timing Firestore query', [
            'query' => 'visitor_profiles.visits_subcollections',
            'visitor_count' => $visitorCount,
            'landmark_count' => count($landmarkSet),
            'records' => count($records),
            'duration_ms' => (int) round((microtime(true) - $start) * 1000),
        ]);

        $visitorProfileCount = $visitorCount;

        return $records;
    }

    /** @param array<string, true> $landmarkSet */
    private function recordFromVisitData(string $id, array $data, array $landmarkSet): ?array
    {
        $landmarkId = trim((string) ($data['landmarkId'] ?? $data['landmark_id'] ?? $data['siteId'] ?? $data['site_id'] ?? ''));
        if (! isset($landmarkSet[$landmarkId])) {
            return null;
        }

        $visitorKey = trim((string) ($data['visitor_key'] ?? $data['visitorId'] ?? $data['visitor_id'] ?? $data['uid'] ?? $data['userId'] ?? ''));
        $visitorName = trim((string) ($data['visitor_name'] ?? $data['visitorName'] ?? $data['name'] ?? $data['userName'] ?? $visitorKey));
        $occurredAt = $this->toCarbon($data['createdAt'] ?? $data['created_at'] ?? $data['timestamp'] ?? $data['date'] ?? null);

        return [
            'id' => $id,
            'landmark_id' => $landmarkId,
            'landmark_name' => trim((string) ($data['landmarkName'] ?? $data['landmark_name'] ?? '')),
            'activity_type' => 'landmark_view',
            'occurred_at' => $occurredAt?->toIso8601String(),
            'visitor_key' => $visitorKey,
            'visitor_name' => $visitorName !== '' ? $visitorName : 'Visitor',
            'visit_count' => max(1, (int) ($data['visit_count'] ?? $data['visitCount'] ?? 1)),
        ];
    }

    /**
     * @param  array<string, true>  $landmarkSet
     * @return array<string, mixed>|null
     */
    private function recordFromVisitDocument(
        mixed $document,
        string $visitorKey,
        string $visitorName,
        mixed $visitorVisitCount,
        array $landmarkSet
    ): ?array {
        $data = $document->data();
        $landmarkId = trim((string) ($data['landmarkId'] ?? $data['landmark_id'] ?? $data['siteId'] ?? $data['site_id'] ?? ''));
        if (! isset($landmarkSet[$landmarkId])) {
            return null;
        }

        $occurredAt = $this->toCarbon($data['createdAt'] ?? $data['timestamp'] ?? $data['date'] ?? null);
        $visitCount = $data['visitCount'] ?? $data['visit_count'] ?? $data['count'] ?? null;

        return [
            'id' => $document->id(),
            'landmark_id' => $landmarkId,
            'landmark_name' => trim((string) ($data['landmarkName'] ?? $data['landmark_name'] ?? '')),
            'activity_type' => 'landmark_view',
            'occurred_at' => $occurredAt?->toIso8601String(),
            'visitor_key' => $visitorKey,
            'visitor_name' => $visitorName !== '' ? $visitorName : 'Visitor',
            'visit_count' => is_numeric($visitCount) ? max(1, (int) $visitCount) : 1,
            'visitor_profile_visit_count' => is_numeric($visitorVisitCount) ? (int) $visitorVisitCount : null,
        ];
    }

    private function toCarbon(mixed $value): ?Carbon
    {
        try {
            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value);
            }
            if (is_object($value) && method_exists($value, 'get')) {
                $value = $value->get();
            }

            return $value ? Carbon::parse($value) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
