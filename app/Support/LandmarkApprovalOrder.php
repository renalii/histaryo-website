<?php

namespace App\Support;

use DateTimeInterface;

final class LandmarkApprovalOrder
{
    public static function compare(array $left, string $leftId, array $right, string $rightId, bool $groupByStatus): int
    {
        if ($groupByStatus) {
            $statusComparison = self::statusRank($left['activation_status'] ?? null)
                <=> self::statusRank($right['activation_status'] ?? null);

            if ($statusComparison !== 0) {
                return $statusComparison;
            }
        }

        $nameComparison = strcasecmp(self::name($left), self::name($right));

        if ($nameComparison !== 0) {
            return $nameComparison;
        }

        $timestampComparison = self::submissionTimestamp($right) <=> self::submissionTimestamp($left);

        return $timestampComparison !== 0
            ? $timestampComparison
            : strcmp($leftId, $rightId);
    }

    public static function comparePortfolioStatusThenName(array $left, string $leftId, array $right, string $rightId, string $direction = 'asc'): int
    {
        $statusComparison = self::portfolioStatusRank($left['activation_status'] ?? $left['status'] ?? null)
            <=> self::portfolioStatusRank($right['activation_status'] ?? $right['status'] ?? null);

        if ($statusComparison !== 0) {
            return $statusComparison;
        }

        $nameComparison = strnatcasecmp(self::name($left), self::name($right));
        if ($nameComparison !== 0) {
            return $direction === 'desc' ? -$nameComparison : $nameComparison;
        }

        return strcmp($leftId, $rightId);
    }

    private static function statusRank(mixed $status): int
    {
        $status = strtolower(trim((string) $status));

        return match ($status === '' ? 'active' : $status) {
            'pending' => 0,
            'active', 'approved' => 1,
            'rejected' => 2,
            default => 3,
        };
    }

    private static function portfolioStatusRank(mixed $status): int
    {
        $status = strtolower(trim((string) $status));

        return match ($status === '' ? 'active' : $status) {
            'active', 'approved' => 0,
            'pending' => 1,
            'rejected' => 2,
            default => 3,
        };
    }

    private static function name(array $landmark): string
    {
        $name = trim((string) ($landmark['name'] ?? ''));

        return $name !== '' ? $name : 'Untitled landmark';
    }

    private static function submissionTimestamp(array $landmark): float
    {
        foreach (['submitted_at', 'created_at'] as $field) {
            $timestamp = self::timestamp($landmark[$field] ?? null);
            if ($timestamp !== null) {
                return $timestamp;
            }
        }

        return 0;
    }

    private static function timestamp(mixed $value): ?float
    {
        if ($value instanceof DateTimeInterface) {
            return (float) $value->format('U.u');
        }

        if (is_object($value)) {
            foreach (['get', 'toDateTime'] as $method) {
                if (method_exists($value, $method)) {
                    return self::timestamp($value->{$method}());
                }
            }
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $timestamp = strtotime($value);

            return $timestamp === false ? null : (float) $timestamp;
        }

        return null;
    }
}
