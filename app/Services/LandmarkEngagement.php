<?php

namespace App\Services;

use Carbon\Carbon;
use Google\Cloud\Firestore\FieldValue;
use Illuminate\Http\Request;

final class LandmarkEngagement
{
    public const COLLECTION = 'landmark_activity';

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

        $this->firebase->firestore()->collection(self::COLLECTION)->add(array_merge([
            'landmark_id' => $landmarkId,
            'activity_type' => $eventType,
            'event_type' => $eventType,
            'visitor_key' => $visitorKey,
            'occurred_at' => FieldValue::serverTimestamp(),
        ], $extra));
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

        $visitors = [];
        $visitorUsers = [];
        $last = null;
        $records = [];

        foreach ($this->firebase->userCollection(FirebaseService::VISITOR_ROLE)->documents() as $visitorDocument) {
            if (! $visitorDocument->exists()) {
                continue;
            }

            $visitorData = $visitorDocument->data();
            $visitorKey = trim((string) ($visitorData['uid'] ?? $visitorDocument->id()));
            $visitorName = trim((string) ($visitorData['fullName'] ?? $visitorData['name'] ?? ''));
            if ($visitorName === '') {
                $visitorName = trim((string) ($visitorData['email'] ?? $visitorKey));
            }

            if ($visitorKey !== '') {
                $visitorUsers[$visitorKey] = true;
            }

            foreach ($visitorDocument->reference()->collection('visits')->documents() as $visitDocument) {
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
                if ($record === null) {
                    continue;
                }

                $records[] = $record;

                $summary['landmark_views'] += 1;

                if ($visitorKey !== '') {
                    $visitors[$visitorKey] = true;
                }

                $occurredAt = $this->toCarbon($record['occurred_at'] ?? null);
                if ($occurredAt !== null) {
                    if ($last === null || $occurredAt->greaterThan($last)) {
                        $last = $occurredAt;
                    }
                    $dayKey = $occurredAt->format('Y-m-d');
                    if (array_key_exists($dayKey, $days)) {
                        $days[$dayKey] += 1;
                    }
                }
            }
        }

        usort($records, function (array $a, array $b): int {
            $aTime = strtotime((string) ($a['occurred_at'] ?? '')) ?: 0;
            $bTime = strtotime((string) ($b['occurred_at'] ?? '')) ?: 0;

            return $bTime <=> $aTime;
        });

        $summary['unique_visitors'] = count($visitors);
        $summary['visitor_users'] = count($visitorUsers);
        $summary['last_activity'] = $last?->toIso8601String();
        $summary['daily_values'] = array_values($days);

        return ['totals' => $summary, 'records' => $records];
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
        $landmarkId = trim((string) ($data['landmarkId'] ?? $data['landmark_id'] ?? ''));
        if (! isset($landmarkSet[$landmarkId])) {
            return null;
        }

        $occurredAt = $this->toCarbon($data['createdAt'] ?? $data['timestamp'] ?? $data['date'] ?? null);

        return [
            'id' => $document->id(),
            'landmark_id' => $landmarkId,
            'landmark_name' => trim((string) ($data['landmarkName'] ?? $data['landmark_name'] ?? '')),
            'activity_type' => 'landmark_view',
            'occurred_at' => $occurredAt?->toIso8601String(),
            'visitor_key' => $visitorKey,
            'visitor_name' => $visitorName !== '' ? $visitorName : 'Visitor',
            'visit_count' => 1,
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
