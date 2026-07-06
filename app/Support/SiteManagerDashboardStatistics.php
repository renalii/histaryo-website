<?php

namespace App\Support;

use Carbon\Carbon;

final class SiteManagerDashboardStatistics
{
    private const DISPLAY_TIMEZONE = 'Asia/Manila';

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  array<string, string>  $landmarkNames
     * @return array<string, mixed>
     */
    public static function fromRecords(array $records, array $landmarkNames, ?Carbon $now = null, ?int $totalVisitorUsers = null): array
    {
        $now = ($now ?? now())->copy()->setTimezone(self::DISPLAY_TIMEZONE);
        $daily = self::periods($now, 7, 'day');
        $weekly = self::periods($now, 8, 'week');
        $monthly = self::periods($now, 12, 'month');
        $yearly = self::periods($now, 5, 'year');
        $yearByYear = self::periods($now, 5, 'year');

        $visitors = [];
        $visitorsByLandmark = [];
        $totals = ['daily' => 0, 'weekly' => 0, 'monthly' => 0, 'yearly' => 0];
        $leaderboard = [];
        $analyticsByLandmark = [
            'all' => self::analyticsBucket($now),
        ];
        $visitorRecords = [];

        foreach ($records as $record) {
            $activityType = strtolower(trim((string) ($record['activity_type'] ?? '')));
            $occurredAt = self::toCarbon($record['occurred_at'] ?? null);

            if (in_array($activityType, ['qr_scan', 'landmark_view'], true)) {
                $landmarkId = trim((string) ($record['landmark_id'] ?? ''));
                $recordLandmarkName = trim((string) ($record['landmark_name'] ?? ''));
                $landmarkName = $landmarkNames[$landmarkId] ?? ($recordLandmarkName !== '' ? $recordLandmarkName : 'Unknown landmark');
                $visitorName = trim((string) ($record['visitor_name'] ?? ''));
                $visitorKey = trim((string) ($record['visitor_key'] ?? ''));
                $visitCount = max(1, (int) ($record['visit_count'] ?? 1));
                if ($visitorKey === '' && $visitorName !== '') {
                    $visitorKey = 'name:'.strtolower($visitorName);
                }
                if ($visitorKey !== '') {
                    $visitors[$visitorKey] = true;

                    if ($landmarkId !== '') {
                        $visitorsByLandmark[$landmarkId][$visitorKey] = true;
                    }
                }

                if ($occurredAt !== null) {
                    self::incrementPeriod($daily, $occurredAt, $visitCount);
                    self::incrementPeriod($weekly, $occurredAt, $visitCount);
                    self::incrementPeriod($monthly, $occurredAt, $visitCount);
                    self::incrementPeriod($yearly, $occurredAt, $visitCount);
                    self::incrementPeriod($yearByYear, $occurredAt, $visitCount);
                    self::incrementAnalytics($analyticsByLandmark['all'], $occurredAt, $now, $visitCount);
                    if ($landmarkId !== '') {
                        $analyticsByLandmark[$landmarkId] ??= self::analyticsBucket($now);
                        self::incrementAnalytics($analyticsByLandmark[$landmarkId], $occurredAt, $now, $visitCount);
                    }

                    if ($occurredAt->isSameDay($now)) {
                        $totals['daily'] += $visitCount;
                    }
                    if ($occurredAt->betweenIncluded($now->copy()->startOfWeek(), $now->copy()->endOfWeek())) {
                        $totals['weekly'] += $visitCount;
                    }
                    if ($occurredAt->isSameMonth($now, true)) {
                        $totals['monthly'] += $visitCount;
                    }
                    if ($occurredAt->isSameYear($now)) {
                        $totals['yearly'] += $visitCount;
                    }
                }

                $recordKey = ($visitorKey !== '' ? $visitorKey : 'visitor').'|'.($landmarkId !== '' ? $landmarkId : 'unknown');
                $visitorRecords[$recordKey] ??= [
                    'visitor_name' => $visitorName !== '' ? $visitorName : ($visitorKey !== '' ? $visitorKey : 'Visitor'),
                    'landmark' => $landmarkName,
                    'landmark_id' => $landmarkId,
                    'visit_count' => 0,
                    'last_visit_at' => null,
                    'last_visit_date' => 'Unknown date',
                ];
                $visitorRecords[$recordKey]['visit_count'] += $visitCount;
                if ($occurredAt !== null) {
                    $previous = self::toCarbon($visitorRecords[$recordKey]['last_visit_at'] ?? null);
                    if ($previous === null || $occurredAt->greaterThan($previous)) {
                        $visitorRecords[$recordKey]['last_visit_at'] = $occurredAt->toIso8601String();
                        $visitorRecords[$recordKey]['last_visit_date'] = self::formatDateTimeLabel($occurredAt, $now);
                    }
                }
            }

            if ($activityType !== 'quiz_attempt') {
                continue;
            }

            $score = $record['quiz_score'] ?? null;
            $scorePercentage = $record['score_percentage'] ?? $record['percentage'] ?? null;
            if (($scorePercentage === null || $scorePercentage === '') && ($score === null || $score === '')) {
                continue;
            }

            $landmarkId = trim((string) ($record['landmark_id'] ?? ''));
            $recordLandmarkName = trim((string) ($record['landmark_name'] ?? ''));
            $leaderboard[] = [
                'visitor_name' => trim((string) ($record['visitor_name'] ?? '')) ?: 'Visitor',
                'landmark_id' => $landmarkId,
                'landmark' => $landmarkNames[$landmarkId] ?? ($recordLandmarkName !== '' ? $recordLandmarkName : 'Unknown landmark'),
                'score' => is_numeric($scorePercentage)
                    ? self::formatPercentage((float) $scorePercentage)
                    : self::formatScore($score, $record['quiz_total'] ?? null),
                'sort_score' => is_numeric($scorePercentage)
                    ? (float) $scorePercentage
                    : self::numericScore($score, $record['quiz_total'] ?? null),
                'completed_at' => $occurredAt?->toIso8601String(),
                'completed_at_label' => $occurredAt !== null
                    ? self::formatDateTimeLabel($occurredAt, $now)
                    : 'Unknown date',
            ];
        }

        usort($leaderboard, function (array $a, array $b): int {
            return ($b['sort_score'] <=> $a['sort_score'])
                ?: (strtotime((string) ($b['completed_at'] ?? '')) <=> strtotime((string) ($a['completed_at'] ?? '')));
        });

        $visitorRecords = array_values($visitorRecords);
        usort($visitorRecords, function (array $a, array $b): int {
            return (strtotime((string) ($b['last_visit_at'] ?? '')) <=> strtotime((string) ($a['last_visit_at'] ?? '')))
                ?: strcasecmp((string) $a['visitor_name'], (string) $b['visitor_name']);
        });

        foreach ($landmarkNames as $landmarkId => $landmarkName) {
            $analyticsByLandmark[$landmarkId] ??= self::analyticsBucket($now);
        }
        $landmarkOptions = [['id' => 'all', 'name' => 'All managed landmarks']];
        foreach ($landmarkNames as $landmarkId => $landmarkName) {
            $landmarkOptions[] = ['id' => (string) $landmarkId, 'name' => $landmarkName];
        }

        return [
            'total_visitors' => $totalVisitorUsers ?? count($visitors),
            'daily_visits' => $totals['daily'],
            'weekly_visits' => $totals['weekly'],
            'monthly_visits' => $totals['monthly'],
            'yearly_visits' => $totals['yearly'],
            'visitors_by_landmark' => array_map('count', $visitorsByLandmark),
            'charts' => [
                'daily' => self::chart($daily),
                'weekly' => self::chart($weekly),
                'monthly' => self::chart($monthly),
                'yearly' => self::chart($yearly),
                'year_by_year' => self::chart($yearByYear),
            ],
            'analytics_by_landmark' => array_map(fn (array $bucket): array => self::serializeAnalyticsBucket($bucket), $analyticsByLandmark),
            'landmark_options' => $landmarkOptions,
            'visitor_records' => array_slice($visitorRecords, 0, 25),
            'leaderboard' => array_slice($leaderboard, 0, 10),
            'leaderboard_by_landmark' => self::leaderboardByLandmark($leaderboard, $landmarkNames),
        ];
    }

    /** @return array<string, array{label:string,start:Carbon,end:Carbon,value:int}> */
    private static function periods(Carbon $now, int $count, string $period): array
    {
        $periods = [];
        for ($offset = $count - 1; $offset >= 0; $offset--) {
            $date = match ($period) {
                'day' => $now->copy()->subDays($offset),
                'week' => $now->copy()->subWeeks($offset),
                'month' => $now->copy()->subMonths($offset),
                default => $now->copy()->subYears($offset),
            };
            $start = match ($period) {
                'day' => $date->copy()->startOfDay(),
                'week' => $date->copy()->startOfWeek(),
                'month' => $date->copy()->startOfMonth(),
                default => $date->copy()->startOfYear(),
            };
            $end = match ($period) {
                'day' => $date->copy()->endOfDay(),
                'week' => $date->copy()->endOfWeek(),
                'month' => $date->copy()->endOfMonth(),
                default => $date->copy()->endOfYear(),
            };
            $label = match ($period) {
                'day' => $date->format('D'),
                'week' => $start->format('M j'),
                'month' => $date->format('M'),
                default => $date->format('Y'),
            };
            $periods[$start->toDateString()] = compact('label', 'start', 'end') + ['value' => 0];
        }

        return $periods;
    }

    /** @param array<string, array{label:string,start:Carbon,end:Carbon,value:int}> $periods */
    private static function incrementPeriod(array &$periods, Carbon $occurredAt, int $amount = 1): void
    {
        foreach ($periods as &$period) {
            if ($occurredAt->betweenIncluded($period['start'], $period['end'])) {
                $period['value'] += max(1, $amount);
                break;
            }
        }
        unset($period);
    }

    /** @param array<string, array{label:string,start:Carbon,end:Carbon,value:int}> $periods */
    private static function chart(array $periods): array
    {
        return [
            'labels' => array_column(array_values($periods), 'label'),
            'values' => array_column(array_values($periods), 'value'),
        ];
    }

    /** @return array<string, mixed> */
    private static function analyticsBucket(Carbon $now): array
    {
        return [
            'totals' => ['daily' => 0, 'weekly' => 0, 'monthly' => 0, 'yearly' => 0],
            'charts' => [
                'daily' => self::periods($now, 7, 'day'),
                'weekly' => self::periods($now, 8, 'week'),
                'monthly' => self::periods($now, 12, 'month'),
                'yearly' => self::periods($now, 5, 'year'),
                'year_by_year' => self::periods($now, 5, 'year'),
            ],
        ];
    }

    private static function incrementAnalytics(array &$bucket, Carbon $occurredAt, Carbon $now, int $amount = 1): void
    {
        $amount = max(1, $amount);
        self::incrementPeriod($bucket['charts']['daily'], $occurredAt, $amount);
        self::incrementPeriod($bucket['charts']['weekly'], $occurredAt, $amount);
        self::incrementPeriod($bucket['charts']['monthly'], $occurredAt, $amount);
        self::incrementPeriod($bucket['charts']['yearly'], $occurredAt, $amount);
        self::incrementPeriod($bucket['charts']['year_by_year'], $occurredAt, $amount);

        if ($occurredAt->isSameDay($now)) {
            $bucket['totals']['daily'] += $amount;
        }
        if ($occurredAt->betweenIncluded($now->copy()->startOfWeek(), $now->copy()->endOfWeek())) {
            $bucket['totals']['weekly'] += $amount;
        }
        if ($occurredAt->isSameMonth($now, true)) {
            $bucket['totals']['monthly'] += $amount;
        }
        if ($occurredAt->isSameYear($now)) {
            $bucket['totals']['yearly'] += $amount;
        }
    }

    /** @param array<string, mixed> $bucket */
    private static function serializeAnalyticsBucket(array $bucket): array
    {
        return [
            'totals' => $bucket['totals'],
            'charts' => [
                'daily' => self::chart($bucket['charts']['daily']),
                'weekly' => self::chart($bucket['charts']['weekly']),
                'monthly' => self::chart($bucket['charts']['monthly']),
                'yearly' => self::chart($bucket['charts']['yearly']),
                'year_by_year' => self::chart($bucket['charts']['year_by_year']),
            ],
        ];
    }

    /** @param list<array<string,mixed>> $leaderboard */
    private static function leaderboardByLandmark(array $leaderboard, array $landmarkNames): array
    {
        $grouped = ['all' => array_slice($leaderboard, 0, 10)];
        foreach ($landmarkNames as $landmarkId => $name) {
            $grouped[(string) $landmarkId] = array_slice(array_values(array_filter(
                $leaderboard,
                fn (array $entry): bool => (string) ($entry['landmark_id'] ?? '') === (string) $landmarkId
            )), 0, 10);
        }

        return $grouped;
    }

    private static function formatScore(mixed $score, mixed $total): string
    {
        if (is_numeric($score) && is_numeric($total) && (float) $total > 0) {
            return ((int) $score).'/'.((int) $total);
        }

        return (string) $score;
    }

    private static function formatPercentage(float $percentage): string
    {
        return rtrim(rtrim(number_format($percentage, 2, '.', ''), '0'), '.').'%';
    }

    private static function numericScore(mixed $score, mixed $total): float
    {
        if (is_string($score) && preg_match('/^\s*(\d+(?:\.\d+)?)\s*\/\s*(\d+(?:\.\d+)?)\s*$/', $score, $matches)) {
            return (float) $matches[2] > 0 ? ((float) $matches[1] / (float) $matches[2]) * 100 : 0;
        }

        return is_numeric($score) ? (float) $score : 0;
    }

    private static function formatDateTimeLabel(Carbon $date, Carbon $now): string
    {
        $date = $date->copy()->setTimezone(self::DISPLAY_TIMEZONE);
        $now = $now->copy()->setTimezone(self::DISPLAY_TIMEZONE);
        $time = $date->format('g:i A');
        if ($date->isSameDay($now)) {
            return 'Today, '.$time;
        }
        if ($date->isSameDay($now->copy()->subDay())) {
            return 'Yesterday, '.$time;
        }

        return $date->format('M j, Y').', '.$time;
    }

    private static function toCarbon(mixed $value): ?Carbon
    {
        try {
            return $value ? Carbon::parse($value)->setTimezone(self::DISPLAY_TIMEZONE) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
