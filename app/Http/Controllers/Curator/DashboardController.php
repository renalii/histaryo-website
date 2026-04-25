<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $firestore;

    public function __construct(FirebaseService $firebase)
    {
        $this->firestore = $firebase->firestore();
    }

    public function index()
    {
        $db = $this->firestore;

        
        $landmarksCount = $this->countDocuments($db->collection('landmarks')->documents());
        $triviaCount    = $this->countDocuments($db->collectionGroup('question_bank')->documents());

        
        $weeks = collect(range(7, 0))->map(function ($i) {
            return Carbon::now()->startOfWeek()->subWeeks($i);
        });

        $weekLabels = [];
        $landmarksPerWeek = [];
        $triviaPerWeek    = [];

        
        $landmarksDocs = $db->collection('landmarks')->documents();
        $triviaDocs    = $db->collectionGroup('question_bank')->documents();

        foreach ($weeks as $startOfWeek) {
            $endOfWeek = $startOfWeek->copy()->endOfWeek();

            
            $weekLabels[] = $startOfWeek->format('M d') . '–' . $endOfWeek->format('M d');

            
            $lCount = 0;
            foreach ($landmarksDocs as $doc) {
                $d = $doc->data();
                if (!empty($d['created_at'])) {
                    $createdAt = Carbon::parse((string)$d['created_at']);
                    if ($createdAt->between($startOfWeek, $endOfWeek)) {
                        $lCount++;
                    }
                }
            }
            $landmarksPerWeek[] = $lCount;

            
            $tCount = 0;
            foreach ($triviaDocs as $doc) {
                $d = $doc->data();
                if (!empty($d['created_at'])) {
                    $createdAt = Carbon::parse((string)$d['created_at']);
                    if ($createdAt->between($startOfWeek, $endOfWeek)) {
                        $tCount++;
                    }
                }
            }
            $triviaPerWeek[] = $tCount;
        }

        
        $recentLogs = [];
        $logsSnap = $db->collection('logs')
            ->orderBy('timestamp', 'DESC')
            ->limit(10)
            ->documents();

        foreach ($logsSnap as $doc) {
            $d = $doc->data();
            $recentLogs[] = [
                'action'    => $d['action'] ?? 'Action',
                'email'     => $d['email'] ?? 'user@example.com',
                'timestamp' => $this->formatRelativeTime($d['timestamp'] ?? null),
            ];
        }

        
        $recentLandmarks = [];
        $landmarksRecentSnap = $db->collection('landmarks')
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->documents();

        foreach ($landmarksRecentSnap as $doc) {
            $d = $doc->data();
            $recentLandmarks[] = [
                'id'         => $doc->id(),
                'name'       => $d['name'] ?? 'Untitled',
                'location'   => $d['location'] ?? null,
                'latitude'   => $d['latitude'] ?? null,
                'longitude'  => $d['longitude'] ?? null,
                'created_at' => $this->formatRelativeTime($d['created_at'] ?? null),
            ];
        }

        $pending = 0;
        foreach (['crowdsourced_tips', 'tips', 'user_tips'] as $tipsCollection) {
            $tipsSnapshot = $db->collection($tipsCollection)->documents();
            foreach ($tipsSnapshot as $tipDoc) {
                if (!$tipDoc->exists()) {
                    continue;
                }

                $tipData = $tipDoc->data();
                $status = strtolower((string) ($tipData['status'] ?? 'pending'));
                if ($status === '' || $status === 'pending') {
                    $pending++;
                }
            }
        }

        return view('curators.dashboard', [
            'stats' => [
                'landmarks' => $landmarksCount,
                'trivia'    => $triviaCount,
                'pending'   => $pending,
                'logs'      => count($recentLogs),
            ],
            'recentLandmarks' => $recentLandmarks,
            'recentLogs'      => $recentLogs,

            
            'weekLabels'       => $weekLabels,
            'landmarksPerWeek' => $landmarksPerWeek,
            'triviaPerWeek'    => $triviaPerWeek,
        ]);
    }

    
    private function countDocuments($snapshot): int
    {
        if (is_object($snapshot) && method_exists($snapshot, 'size')) {
            return (int) $snapshot->size();
        }

        if (is_object($snapshot) && method_exists($snapshot, 'rows')) {
            $rows = $snapshot->rows();
            return is_array($rows) ? count($rows) : (int) iterator_count($rows);
        }

        $count = 0;
        foreach ($snapshot as $_) { $count++; }
        return $count;
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
