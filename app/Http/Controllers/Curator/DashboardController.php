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

class DashboardController extends Controller
{
    protected $firestore;

    public function __construct(
        protected FirebaseService $firebase,
        protected LandmarkEngagement $engagement,
        protected QuizResultService $quizResults
    ) {
        $this->firestore = $firebase->firestore();
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
        $landmarkId = CuratorAssignedLandmark::id();
        $landmark = [
            'id' => $landmarkId,
            'name' => 'Assigned landmark',
            'status' => 'Unavailable',
        ];

        if ($landmarkId !== null) {
            $snapshot = $this->firestore->collection('landmarks')->document($landmarkId)->snapshot();
            if ($snapshot->exists()) {
                $data = $snapshot->data();
                $activationStatus = strtolower(trim((string) ($data['activation_status'] ?? 'active')));
                $landmark['name'] = trim((string) ($data['name'] ?? '')) ?: 'Untitled landmark';
                $landmark['status'] = LandmarkActivation::label($activationStatus);
            }
        }

        $activity = $this->engagement->analyticsForLandmarks($landmarkId !== null ? [$landmarkId] : []);
        $quizResults = $landmarkId !== null ? $this->quizResults->forLandmark($landmarkId) : [];
        $statistics = SiteManagerDashboardStatistics::fromRecords(
            array_merge($activity['records'], $quizResults),
            $landmarkId !== null ? [$landmarkId => $landmark['name']] : []
        );
        $landmark['total_visitors'] = $statistics['total_visitors'];

        return view('curators.dashboard', [
            'assignedLandmark' => $landmark,
            'visitorStatistics' => $statistics,
        ]);
    }
}
