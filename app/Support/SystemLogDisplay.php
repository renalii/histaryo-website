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

    public static function formatAction(string $action): string
    {
        $action = (string) preg_replace('/\s*\(auto-QR:\s*LM-[a-f0-9]{6}\)/i', '', $action);

        $replacements = [
            'Landmark Manager' => 'Site Manager',
            'Landmark_manager' => 'Site Manager',
            'landmark_manager' => 'Site Manager',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $action);
    }
}
