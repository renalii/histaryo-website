<?php

namespace Tests\Unit;

use App\Services\FirebaseService;
use App\Services\LandmarkEngagement;
use App\Support\ArrayDocumentSnapshot;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class LandmarkEngagementTest extends TestCase
{
    public function test_it_normalizes_nested_visitor_visit_documents(): void
    {
        $service = new LandmarkEngagement($this->createMock(FirebaseService::class));
        $method = new ReflectionMethod(LandmarkEngagement::class, 'recordFromVisitDocument');
        $method->setAccessible(true);

        $record = $method->invoke($service, new ArrayDocumentSnapshot('visit-1', [
            'landmarkId' => '7b82a61642747ffb928',
            'createdAt' => '2026-07-02T08:35:53.034Z',
            'date' => 'Thu Jul 02 2026',
            'timestamp' => '2026-07-02T08:35:53.034Z',
        ]), 'visitor-1', 'Rie', 7, ['7b82a61642747ffb928' => true]);

        $this->assertSame('visit-1', $record['id']);
        $this->assertSame('landmark_view', $record['activity_type']);
        $this->assertSame('visitor-1', $record['visitor_key']);
        $this->assertSame('Rie', $record['visitor_name']);
        $this->assertSame('7b82a61642747ffb928', $record['landmark_id']);
        $this->assertSame(1, $record['visit_count']);
        $this->assertSame(7, $record['visitor_profile_visit_count']);
        $this->assertSame('2026-07-02T08:35:53+00:00', $record['occurred_at']);
    }

    public function test_it_matches_nested_visit_documents_by_site_id_and_visit_count(): void
    {
        $service = new LandmarkEngagement($this->createMock(FirebaseService::class));
        $method = new ReflectionMethod(LandmarkEngagement::class, 'recordFromVisitDocument');
        $method->setAccessible(true);

        $record = $method->invoke($service, new ArrayDocumentSnapshot('visit-2', [
            'siteId' => 'site-1',
            'visitCount' => 4,
            'createdAt' => '2026-07-10T08:00:00.000Z',
        ]), 'visitor-2', 'Mara', null, ['site-1' => true]);

        $this->assertSame('site-1', $record['landmark_id']);
        $this->assertSame(4, $record['visit_count']);
    }
}
