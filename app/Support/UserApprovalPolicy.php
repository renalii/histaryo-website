<?php

namespace App\Support;

final class UserApprovalPolicy
{
    /** Super Admin may approve registrations for this role only. */
    public static function superAdminApprovesRole(string $role): bool
    {
        return strtolower(trim($role)) === 'site_manager';
    }

    /** Site Manager may approve registrations for this role only. */
    public static function siteManagerApprovesRole(string $role): bool
    {
        return strtolower(trim($role)) === 'curator';
    }

    /** Roles that may use the app without administrator or manager approval. */
    public static function roleRequiresApproval(string $role): bool
    {
        return strtolower(trim($role)) !== 'visitor';
    }

    public static function effectiveRequiresApproval(string $role, mixed $requiresApprovalFlag): bool
    {
        if (! self::roleRequiresApproval($role)) {
            return false;
        }

        return FirestoreBool::isTrue($requiresApprovalFlag);
    }

    /**
     * Firestore fields to clear stale approval gates for visitors.
     *
     * @return array<string, mixed>|null
     */
    public static function visitorAutoApprovalPatch(array $profile): ?array
    {
        $role = strtolower((string) ($profile['role'] ?? 'visitor'));
        if ($role !== 'visitor') {
            return null;
        }

        $requiresApproval = FirestoreBool::isTrue($profile['requires_approval'] ?? null);
        $status = strtolower((string) ($profile['approval_status'] ?? 'approved'));

        if (! $requiresApproval && $status !== 'pending' && $status !== 'rejected') {
            return null;
        }

        return [
            'approval_status' => 'approved',
            'requires_approval' => false,
            'updated_at' => now()->toDateTimeString(),
        ];
    }
}
