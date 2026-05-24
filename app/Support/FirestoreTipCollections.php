<?php

namespace App\Support;

use Google\Cloud\Firestore\DocumentReference;

/**
 * Shared Firestore crowd-tip collection names and document field resolution.
 */
final class FirestoreTipCollections
{
    /** @var list<string> */
    private const DEFAULT_NAMES = ['crowdsourced_tips', 'crowdsource_tips', 'tips', 'user_tips'];

    /**
     * All tip collection document ids to read (defaults + optional env / config).
     *
     * @return list<string>
     */
    public static function names(): array
    {
        $extra = config('services.firebase.firestore_tip_collection_names', []);
        if (! is_array($extra)) {
            $extra = [];
        }

        $merged = array_merge(self::DEFAULT_NAMES, $extra);
        $out = [];
        foreach ($merged as $name) {
            $name = trim((string) $name);
            if ($name !== '' && ! in_array($name, $out, true)) {
                $out[] = $name;
            }
        }

        return $out;
    }

    public static function usesBrowseableScope(string $collectionName): bool
    {
        return $collectionName === 'crowdsourced_tips' || $collectionName === 'crowdsource_tips';
    }

    /**
     * Resolve a landmark document id from raw Firestore field values (string, number, or DocumentReference).
     *
     * @param  mixed  $value
     */
    public static function normalizeLandmarkIdValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_string($value) || is_int($value) || is_float($value)) {
            return trim((string) $value);
        }
        if ($value instanceof DocumentReference) {
            return trim((string) $value->id());
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function landmarkIdFromData(array $data): string
    {
        $topKeys = [
            'landmark_id', 'landmarkId', 'landmarkID', 'site_id', 'siteId',
            'lm_id', 'landmark_doc_id', 'landmarkDocId',
        ];
        foreach ($topKeys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $id = self::normalizeLandmarkIdValue($data[$key]);
            if ($id !== '') {
                return $id;
            }
        }

        if (array_key_exists('landmark', $data)) {
            $lm = $data['landmark'];
            if (is_string($lm) || is_numeric($lm)) {
                $id = self::normalizeLandmarkIdValue($lm);
                if ($id !== '') {
                    return $id;
                }
            }
            if (is_array($lm)) {
                foreach (['id', 'landmark_id', 'landmarkId', 'site_id', 'siteId'] as $nk) {
                    if (! array_key_exists($nk, $lm)) {
                        continue;
                    }
                    $id = self::normalizeLandmarkIdValue($lm[$nk]);
                    if ($id !== '') {
                        return $id;
                    }
                }
            } else {
                $id = self::normalizeLandmarkIdValue($lm);
                if ($id !== '') {
                    return $id;
                }
            }
        }

        return '';
    }

    /** Comma-separated list for Laravel `in:` validation rules. */
    public static function validationInRule(): string
    {
        return implode(',', self::names());
    }
}
