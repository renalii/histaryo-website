<?php

namespace Tests\Unit;

use App\Support\LandmarkVisibility;
use PHPUnit\Framework\TestCase;

class LandmarkVisibilityTest extends TestCase
{
    public function test_active_legacy_landmarks_default_to_published(): void
    {
        $this->assertSame('published', LandmarkVisibility::normalize(null, 'active'));
        $this->assertTrue(LandmarkVisibility::isPublic('', 'active'));
    }

    public function test_pending_legacy_landmarks_default_to_hidden(): void
    {
        $this->assertSame('hidden', LandmarkVisibility::normalize(null, 'pending'));
        $this->assertFalse(LandmarkVisibility::isPublic('', 'pending'));
    }

    public function test_hidden_is_authorized_but_not_public(): void
    {
        $this->assertFalse(LandmarkVisibility::isPublic('hidden', 'active'));
        $this->assertTrue(LandmarkVisibility::isAuthorizedListingVisible('hidden', 'active'));
    }

    public function test_archived_is_excluded_from_active_listings(): void
    {
        $this->assertFalse(LandmarkVisibility::isPublic('archived', 'active'));
        $this->assertFalse(LandmarkVisibility::isAuthorizedListingVisible('archived', 'active'));
    }
}
