<?php

namespace App\Support;

final class LandmarkVisibility
{
    public const PUBLISHED = 'published';

    public const ARCHIVED = 'archived';

    public const HIDDEN = 'hidden';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::PUBLISHED, self::ARCHIVED, self::HIDDEN];
    }

    public static function normalize(mixed $visibility, string $activationStatus = 'active'): string
    {
        $visibility = strtolower(trim((string) $visibility));
        if (in_array($visibility, self::values(), true)) {
            return $visibility;
        }

        return LandmarkActivation::isBrowsable($activationStatus)
            ? self::PUBLISHED
            : self::HIDDEN;
    }

    public static function label(string $visibility): string
    {
        return ucfirst(self::normalize($visibility));
    }

    public static function isPublic(string $visibility, string $activationStatus = 'active'): bool
    {
        return LandmarkActivation::isBrowsable($activationStatus)
            && self::normalize($visibility, $activationStatus) === self::PUBLISHED;
    }

    public static function isAuthorizedListingVisible(string $visibility, string $activationStatus = 'active'): bool
    {
        return LandmarkActivation::isBrowsable($activationStatus)
            && self::normalize($visibility, $activationStatus) !== self::ARCHIVED;
    }
}
