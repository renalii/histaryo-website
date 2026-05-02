<?php

namespace App\Support;

final class FirestoreBool
{
    /** Normalized truth read for mixed Firestore/JSON-ish values */
    public static function isTrue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value !== 0;
        }

        if (is_string($value)) {
            $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            return $parsed === true;
        }

        return false;
    }
}
