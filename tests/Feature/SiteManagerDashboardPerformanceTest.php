<?php

namespace Tests\Feature;

use App\Services\FirebaseService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class SiteManagerDashboardPerformanceTest extends TestCase
{
    public function test_cached_site_manager_dashboard_does_not_initialize_firebase_clients(): void
    {
        Cache::put('site-manager:manager-1:dashboard:v4', [
            'landmarkCount' => 0,
            'curatorCount' => 0,
            'siteManagerStatistics' => [
                'total_visitors' => 0,
                'leaderboard' => [],
                'charts' => [],
            ],
        ], now()->addMinutes(5));

        $firebase = Mockery::mock(FirebaseService::class);
        $firebase->shouldNotReceive('auth');
        $firebase->shouldNotReceive('firestore');
        $this->app->instance(FirebaseService::class, $firebase);

        $this->withSession([
            'uid' => 'manager-1',
            'role' => 'site_manager',
            'email' => 'manager@example.com',
        ])->get('/sitemanager')->assertOk();
    }
}
