<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use App\Services\CuratorAccessibleLandmarks;
use App\Services\CuratorBrowseableLandmarks;
use App\Services\FirebaseService;
use App\Support\CuratorAssignedLandmark;
use App\Support\FirestoreTipCollections;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $firestore;

    public function __construct(protected FirebaseService $firebase)
    {
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
        $db = $this->firestore;

        $browseableIds = CuratorAssignedLandmark::browseableIds();

        $landmarksCount = count($browseableIds);

        $quizCount = 0;
        foreach ($browseableIds as $lid) {
            $quizCount += iterator_count($db->collection('question_bank')
                ->where('landmark_id', '==', $lid)
                ->documents());
        }

        $weeks = collect(range(7, 0))->map(function ($i) {
            return Carbon::now()->startOfWeek()->subWeeks($i);
        });

        $weekLabels = [];
        $landmarksPerWeek = [];
        $quizPerWeek = [];

        $scopedLandmarkDocs = [];
        foreach ($browseableIds as $lid) {
            $snap = $db->collection('landmarks')->document($lid)->snapshot();
            if ($snap->exists()) {
                $scopedLandmarkDocs[] = $snap;
            }
        }

        $quizForWeeksDocs = [];
        foreach ($browseableIds as $lid) {
            $quizForWeeksDocs = array_merge(
                $quizForWeeksDocs,
                iterator_to_array($db->collection('question_bank')->where('landmark_id', '==', $lid)->documents())
            );
        }

        foreach ($weeks as $startOfWeek) {
            $endOfWeek = $startOfWeek->copy()->endOfWeek();

            $weekLabels[] = $startOfWeek->format('M d').'–'.$endOfWeek->format('M d');

            $lCount = 0;
            foreach ($scopedLandmarkDocs as $scopedLandmarkDoc) {
                if (! $scopedLandmarkDoc->exists()) {
                    continue;
                }
                $d = $scopedLandmarkDoc->data();
                if (empty($d['created_at'])) {
                    continue;
                }
                try {
                    $createdAt = Carbon::parse((string) $d['created_at']);
                    if ($createdAt->between($startOfWeek, $endOfWeek)) {
                        $lCount++;
                    }
                } catch (\Exception $e) {
                }
            }
            $landmarksPerWeek[] = $lCount;

            $tCount = 0;
            foreach ($quizForWeeksDocs as $doc) {
                if (! $doc->exists()) {
                    continue;
                }
                $td = $doc->data();
                if (empty($td['created_at'])) {
                    continue;
                }
                try {
                    $createdAt = Carbon::parse((string) $td['created_at']);
                    if ($createdAt->between($startOfWeek, $endOfWeek)) {
                        $tCount++;
                    }
                } catch (\Exception $e) {
                }
            }
            $quizPerWeek[] = $tCount;
        }

        $recentLogs = [];
        foreach ($db->collection('logs')->orderBy('timestamp', 'DESC')->limit(10)->documents() as $doc) {
            if (! $doc->exists()) {
                continue;
            }
            $d = $doc->data();
            $recentLogs[] = [
                'action' => $d['action'] ?? 'Action',
                'email' => $d['email'] ?? 'user@example.com',
                'timestamp' => $this->formatRelativeTime($d['timestamp'] ?? null),
            ];
        }

        $recentLandmarks = [];
        foreach ($scopedLandmarkDocs as $scopedLandmarkDoc) {
            if (! $scopedLandmarkDoc->exists()) {
                continue;
            }
            $ld = $scopedLandmarkDoc->data();
            $recentLandmarks[] = [
                'id' => $scopedLandmarkDoc->id(),
                'name' => $ld['name'] ?? 'Untitled',
                'location' => $ld['location'] ?? null,
                'latitude' => $ld['latitude'] ?? null,
                'longitude' => $ld['longitude'] ?? null,
                'created_at' => $this->formatRelativeTime($ld['created_at'] ?? null),
            ];
        }

        $writableSet = $this->tipLandmarkKeySet(CuratorAssignedLandmark::writableIds());
        $browseableSet = $this->tipLandmarkKeySet($browseableIds);
        $pending = 0;
        foreach (FirestoreTipCollections::names() as $tipsCollection) {
            $scopeSet = FirestoreTipCollections::usesBrowseableScope($tipsCollection) ? $browseableSet : $writableSet;
            foreach ($db->collection($tipsCollection)->documents() as $tipDoc) {
                if (! $tipDoc->exists()) {
                    continue;
                }
                $tipData = $tipDoc->data();
                $lid = FirestoreTipCollections::landmarkIdFromData($tipData);
                if ($scopeSet !== []) {
                    if ($lid === '' || ! isset($scopeSet[$lid])) {
                        continue;
                    }
                }
                $status = strtolower((string) ($tipData['status'] ?? 'pending'));
                if ($status === '' || $status === 'pending') {
                    $pending++;
                }
            }
        }

        return view('curators.dashboard', [
            'stats' => [
                'landmarks' => $landmarksCount,
                'quiz' => $quizCount,
                'pending' => $pending,
                'logs' => count($recentLogs),
            ],
            'recentLandmarks' => $recentLandmarks,
            'recentLogs' => $recentLogs,
            'weekLabels' => $weekLabels,
            'landmarksPerWeek' => $landmarksPerWeek,
            'quizPerWeek' => $quizPerWeek,
        ]);
    }

    /**
     * @param  list<string>  $ids
     * @return array<string, true>
     */
    private function tipLandmarkKeySet(array $ids): array
    {
        $set = [];
        foreach ($ids as $id) {
            $k = trim((string) $id);
            if ($k !== '') {
                $set[$k] = true;
            }
        }

        return $set;
    }

    private function formatRelativeTime($value): string
    {
        if (!$value) return '—';

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->diffForHumans();
        }

        if (is_object($value) && method_exists($value, 'get')) {
            try {
                $dt = $value->get();
                if ($dt instanceof \DateTimeInterface) {
                    return Carbon::instance($dt)->diffForHumans();
                }
            } catch (\Throwable $e) {
                // fall through
            }
        }

        try {
            return Carbon::parse((string) $value)->diffForHumans();
        } catch (\Throwable $e) {
            return '—';
        }
    }
}
