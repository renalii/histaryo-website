<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use App\Services\CuratorAccessibleLandmarks;
use App\Services\CuratorBrowseableLandmarks;
use App\Services\FirebaseService;
use App\Services\LandmarkEngagement;
use App\Services\QuizResultService;
use App\Support\CuratorAssignedLandmark;
use App\Support\LandmarkActivation;
use App\Support\SiteManagerDashboardStatistics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected $firestore = null;

    public function __construct(
        protected FirebaseService $firebase,
        protected LandmarkEngagement $engagement,
        protected QuizResultService $quizResults
    ) {}

    private function firestore()
    {
        return $this->firestore ??= $this->firebase->firestore();
    }

    public function pendingAssignment(Request $request)
    {
        $uid = $request->session()->get('uid');
        if (! is_string($uid) || $uid === '') {
            return redirect()->route('login');
        }

        $userDoc = $this->firebase->userDocument($uid, 'curator')->snapshot();
        $raw = $userDoc->exists() ? ($userDoc['assigned_landmark_id'] ?? null) : null;
        $trimmed = is_string($raw) ? trim($raw) : '';

        if ($trimmed !== '') {
            $request->session()->put('assigned_landmark_id', $trimmed);
            $request->session()->put('browseable_landmark_ids', CuratorBrowseableLandmarks::resolveIds($this->firebase, $trimmed));
            $request->session()->put('writable_landmark_ids', CuratorAccessibleLandmarks::resolveIds($this->firebase, $trimmed));

            return redirect()->route('curators.dashboard')->with(
                'success',
                'Your landmark assignment is now active. Welcome to your dashboard.'
            );
        }

        return view('curators.pending-assignment');
    }

    public function index()
    {
        $start = microtime(true);
        $landmarkId = CuratorAssignedLandmark::id();
        $landmark = [
            'id' => $landmarkId,
            'name' => 'Assigned landmark',
            'status' => 'Unavailable',
        ];

        if ($landmarkId !== null) {
            $landmark = Cache::remember(
                'curator:dashboard:landmark:'.$landmarkId,
                now()->addMinutes(10),
                function () use ($landmarkId, $landmark): array {
                    $queryStart = microtime(true);
                    $snapshot = $this->firestore()->collection('landmarks')->document($landmarkId)->snapshot();
                    Log::info('Timing Firestore query', [
                        'query' => 'curator_dashboard.landmark_snapshot',
                        'landmark_id' => $landmarkId,
                        'duration_ms' => (int) round((microtime(true) - $queryStart) * 1000),
                    ]);

                    if (! $snapshot->exists()) {
                        return $landmark;
                    }

                    $data = $snapshot->data();
                    $activationStatus = strtolower(trim((string) ($data['activation_status'] ?? 'active')));

                    return [
                        'id' => $landmarkId,
                        'name' => trim((string) ($data['name'] ?? '')) ?: 'Untitled landmark',
                        'status' => LandmarkActivation::label($activationStatus),
                    ];
                }
            );
        }

        $statistics = Cache::remember(
            'curator:dashboard:statistics:'.($landmarkId ?? 'none'),
            now()->addMinutes(3),
            function () use ($landmarkId, $landmark): array {
                $activity = $this->engagement->analyticsForLandmarks($landmarkId !== null ? [$landmarkId] : []);
                $quizResults = $landmarkId !== null ? $this->quizResults->forLandmark($landmarkId) : [];

                return SiteManagerDashboardStatistics::fromRecords(
                    array_merge($activity['records'], $quizResults),
                    $landmarkId !== null ? [$landmarkId => $landmark['name']] : []
                );
            }
        );
        $landmark['total_visitors'] = $statistics['total_visitors'];

        Log::info('Timing curator page', [
            'route' => 'curators.dashboard',
            'duration_ms' => (int) round((microtime(true) - $start) * 1000),
        ]);

        return view('curators.dashboard', [
            'assignedLandmark' => $landmark,
            'visitorStatistics' => $statistics,
        ]);
    }
}
