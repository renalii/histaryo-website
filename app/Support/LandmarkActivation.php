<?php

namespace App\Support;

final class LandmarkActivation
{
    public static function label(string $status): string
    {
        return match (strtolower($status)) {
            'pending' => 'Pending approval',
            'rejected' => 'Rejected',
            'active' => 'Active',
            default => ucfirst($status !== '' ? $status : 'active'),
        };
    }

    public static function isBrowsable(string $status): bool
    {
        $status = strtolower($status);

        return $status !== 'pending' && $status !== 'rejected';
    }
}
