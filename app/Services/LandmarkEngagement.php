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
     *   records:list<array{id:string,landmark_id:string,activity_type:string,occurred_at:?string}>
     * }
     */
    public function analyticsForLandmarks(array $landmarkIds): array
    {
        $summary = [
            'qr_scans' => 0,
            'landmark_views' => 0,
            'quiz_attempts' => 0,
            'unique_visitors' => 0,
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
        $last = null;
        $records = [];
        $documents = $this->firebase->firestore()->collection(self::COLLECTION)->documents();

        foreach ($documents as $doc) {
            if (! $doc->exists()) {
                continue;
            }

            $data = $doc->data();
            $landmarkId = trim((string) ($data['landmark_id'] ?? ''));
            if ($landmarkId === '') {
                $landmarkId = trim((string) ($data['landmarkId'] ?? ''));
            }
            if (! isset($landmarkSet[$landmarkId])) {
                continue;
            }

            $eventType = strtolower(trim((string) ($data['activity_type'] ?? '')));
            if ($eventType === '') {
                $eventType = strtolower(trim((string) ($data['event_type'] ?? '')));
            }
            $occurredAt = $this->toCarbon($data['occurred_at'] ?? $data['created_at'] ?? $data['timestamp'] ?? null);
            $records[] = [
                'id' => $doc->id(),
                'landmark_id' => $landmarkId,
                'activity_type' => $eventType,
                'occurred_at' => $occurredAt?->toIso8601String(),
            ];

            $metric = match ($eventType) {
                'qr_scan' => 'qr_scans',
                'landmark_view' => 'landmark_views',
                'quiz_attempt' => 'quiz_attempts',
                default => null,
            };
            if ($metric === null) {
                continue;
            }
            $summary[$metric]++;

            $visitorKey = trim((string) ($data['visitor_key'] ?? $data['visitor_id'] ?? $data['uid'] ?? ''));
            if ($visitorKey !== '') {
                $visitors[$visitorKey] = true;
            }

            if ($occurredAt !== null) {
                if ($last === null || $occurredAt->greaterThan($last)) {
                    $last = $occurredAt;
                }
                $dayKey = $occurredAt->format('Y-m-d');
                if (array_key_exists($dayKey, $days)) {
                    $days[$dayKey]++;
                }
            }
        }

        usort($records, function (array $a, array $b): int {
            $aTime = strtotime((string) ($a['occurred_at'] ?? '')) ?: 0;
            $bTime = strtotime((string) ($b['occurred_at'] ?? '')) ?: 0;

            return $bTime <=> $aTime;
        });

        $summary['unique_visitors'] = count($visitors);
        $summary['last_activity'] = $last?->toIso8601String();
        $summary['daily_values'] = array_values($days);

        return ['totals' => $summary, 'records' => $records];
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
