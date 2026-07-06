<?php

namespace App\Support;

final class SystemLogDisplay
{
    public static function roleLabel(?string $role): string
    {
        $role = strtolower(trim((string) $role));

        return match ($role) {
            'site_manager', 'landmark_manager' => 'Site Manager',
            'admin' => 'Admin',
            'curator' => 'Curator',
            'visitor' => 'Visitor',
            '' => 'N/A',
            default => ucwords(str_replace('_', ' ', $role)),
        };
    }

    public static function roleCssClass(?string $role): string
    {
        $role = strtolower(trim((string) $role));

        return match ($role) {
            'admin' => 'role-admin',
            'curator' => 'role-curator',
            'site_manager', 'landmark_manager' => 'role-site_manager',
            default => 'role-na',
        };
    }

    public static function roleForLog(?string $role, string $action): string
    {
        $action = strtolower($action);

        return match (true) {
            str_contains($action, 'site manager')
                || str_contains($action, 'created curator account')
                || str_contains($action, 'updated curator account')
                || str_contains($action, 'deleted curator account')
                || str_contains($action, 'deactivated curator account')
                || str_contains($action, 'created landmark')
                || str_contains($action, 'submitted landmark for approval')
                || str_contains($action, 'updated landmark')
                || str_contains($action, 'deleted landmark') => 'site_manager',
            str_contains($action, 'admin') => 'admin',
            str_contains($action, 'curator') => 'curator',
            str_contains($action, 'visitor') => 'visitor',
            default => (string) $role,
        };
    }

    /** @param array<string,mixed> $data */
    public static function formatAction(string $action, array $data = [], ?string $role = null): string
    {
        $action = (string) preg_replace('/\s*\(auto-QR:\s*LM-[a-f0-9]{6}\)/i', '', $action);

        $replacements = [
            'Landmark Manager' => 'Site Manager',
            'Landmark_manager' => 'Site Manager',
            'landmark_manager' => 'Site Manager',
        ];

        $action = str_replace(array_keys($replacements), array_values($replacements), trim($action));
        $action = preg_replace('/\s+successfully\.?$/i', '', $action) ?? $action;
        $roleLabel = self::roleLabel($role);

        if (strcasecmp($action, 'Logged in') === 0 && $roleLabel !== 'N/A') {
            return $roleLabel.' logged in';
        }
        if (strcasecmp($action, 'Logged out') === 0 && $roleLabel !== 'N/A') {
            return $roleLabel.' logged out';
        }

        $targetFields = [
            'curator_email',
            'landmark_name',
            'landmarkName',
            'landmark_id',
            'landmarkId',
            'qr_code',
            'qrCode',
            'code',
            'quiz_question',
            'question',
            'tip_id',
            'tipId',
            'uid',
        ];
        $target = '';
        foreach ($targetFields as $field) {
            $candidate = trim((string) ($data[$field] ?? ''));
            if ($candidate !== '') {
                $target = $candidate;
                break;
            }
        }
        if ($target === '' && str_contains($action, ':')) {
            $target = trim((string) preg_replace('/^.*?:\s*/', '', $action));
        }

        $lower = strtolower($action);
        $normalized = match (true) {
            str_contains($lower, 'created curator account') => 'Site Manager created curator account',
            str_contains($lower, 'updated curator account') => 'Site Manager updated curator account',
            str_contains($lower, 'deleted curator account') => 'Site Manager deleted curator account',
            str_contains($lower, 'deactivated curator account') => 'Site Manager deactivated curator account',
            str_contains($lower, 'created landmark') => 'Site Manager created landmark',
            str_contains($lower, 'submitted landmark for approval') => 'Site Manager submitted landmark for approval',
            str_contains($lower, 'updated landmark') => 'Site Manager updated landmark',
            str_contains($lower, 'deleted landmark') => 'Site Manager deleted landmark',
            str_contains($lower, 'landmark approved') || str_contains($lower, 'approved landmark') => 'Admin approved landmark',
            str_contains($lower, 'landmark rejected') || str_contains($lower, 'rejected landmark') => 'Admin rejected landmark',
            str_contains($lower, 'generated qr') || str_contains($lower, 'qr mapping created') => 'Generated QR code',
            str_contains($lower, 'downloaded qr') => 'Downloaded QR code',
            str_contains($lower, 'accepted a user tip') || str_contains($lower, 'accepted visitor tip') => 'Curator accepted visitor tip',
            str_contains($lower, 'rejected a user tip') || str_contains($lower, 'rejected visitor tip') => 'Curator rejected visitor tip',
            str_contains($lower, 'added quiz') => 'Curator added quiz question',
            str_contains($lower, 'updated quiz') => 'Curator updated quiz question',
            str_contains($lower, 'deleted quiz') => 'Curator deleted quiz question',
            default => $action,
        };

        if ($target !== '' && ! str_contains($normalized, ':')) {
            return $normalized.': '.$target;
        }

        return $normalized;
    }
}
