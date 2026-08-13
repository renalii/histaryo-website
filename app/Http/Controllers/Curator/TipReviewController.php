<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use App\Support\FirestoreTipCollections;
use App\Services\FirebaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class TipReviewController extends Controller
{
    protected $firestore = null;

    protected FirebaseService $firebase;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebase = $firebaseService;
    }

    private function firestore()
    {
        return $this->firestore ??= $this->firebase->firestore();
    }

    public function index(Request $request)
    {
        $statusFilter = strtolower((string) $request->query('status', 'pending'));
        if (!in_array($statusFilter, ['all', 'pending', 'accepted', 'rejected'], true)) {
            $statusFilter = 'pending';
        }

        $tips = $this->tipsForLandmark($this->assignedLandmarkId(), $statusFilter);
        $perPage = 5;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $offset = max(0, ($currentPage - 1) * $perPage);
        $pageItems = array_slice($tips, $offset, $perPage);

        $tips = new LengthAwarePaginator(
            $pageItems,
            count($tips),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        return view('curators.tips.index', compact('tips', 'statusFilter'));
    }

    public function tipsForLandmark(string $landmarkId, string $statusFilter = 'all'): array
    {
        if ($landmarkId === '') {
            return [];
        }

        $cacheKey = 'curator:tips:'.$landmarkId.':'.$statusFilter;
        return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($landmarkId, $statusFilter): array {
            return $this->loadTipsForLandmark($landmarkId, $statusFilter);
        });
    }

    private function loadTipsForLandmark(string $landmarkId, string $statusFilter): array
    {
        $start = microtime(true);
        $tips = [];
        $seenTips = [];
        foreach (FirestoreTipCollections::names() as $collectionName) {
            foreach (['landmark_id', 'landmarkId'] as $landmarkField) {
                try {
                    $query = $this->firestore()
                        ->collection($collectionName)
                        ->where($landmarkField, '==', $landmarkId);
                    if (in_array($statusFilter, ['accepted', 'rejected'], true)) {
                        $query = $query->where('status', '==', $statusFilter);
                    }
                    $queryStart = microtime(true);
                    $tipsSnapshot = $query->documents([
                            'maxRetries' => 0,
                            'requestTimeout' => (float) config('services.firebase.tip_query_timeout', 3),
                            'retries' => 0,
                        ]);
                    Log::info('Timing Firestore query', [
                        'query' => 'curator_tips.by_landmark_status',
                        'collection' => $collectionName,
                        'landmark_field' => $landmarkField,
                        'status' => $statusFilter,
                        'duration_ms' => (int) round((microtime(true) - $queryStart) * 1000),
                    ]);
                } catch (\Throwable $exception) {
                    Log::warning('Unable to load curator landmark tips from Firestore.', [
                        'collection' => $collectionName,
                        'landmark_field' => $landmarkField,
                        'landmark_id' => $landmarkId,
                        'exception' => $exception->getMessage(),
                    ]);

                    continue;
                }

                foreach ($tipsSnapshot as $doc) {
                    if (!$doc->exists()) {
                        continue;
                    }

                    $tipKey = $collectionName.'/'.$doc->id();
                    if (isset($seenTips[$tipKey])) {
                        continue;
                    }

                    $data = $doc->data();
                    $tipLandmarkId = FirestoreTipCollections::landmarkIdFromData($data);
                    if ($tipLandmarkId !== $landmarkId) {
                        continue;
                    }

                    $status = strtolower((string) ($data['status'] ?? 'pending'));

                    if ($status === '') {
                        $status = 'pending';
                    }

                    $status = in_array($status, ['accepted', 'rejected', 'pending'], true) ? $status : 'pending';
                    if ($statusFilter !== 'all' && $status !== $statusFilter) {
                        continue;
                    }

                    $createdAtRaw = $data['created_at'] ?? $data['createdAt'] ?? null;
                    $reviewedAtRaw = $data['reviewed_at'] ?? $data['reviewedAt'] ?? null;

                    $submittedEmail = (string) ($data['submitted_email'] ?? $data['submittedBy'] ?? $data['user_email'] ?? '');
                    $submittedBy = (string) ($data['submitted_by'] ?? $data['submittedBy'] ?? $data['user_name'] ?? $data['userId'] ?? '');

                    if ($submittedBy === '' && $submittedEmail !== '') {
                        $submittedBy = $submittedEmail;
                    }

                    if ($submittedBy === '') {
                        $submittedBy = 'Unknown User';
                    }

                    $title = (string) ($data['title'] ?? '');
                    if ($title === '' && is_array($data['tags'] ?? null)) {
                        $title = (string) (($data['tags']['title'] ?? '') ?: '');
                    }

                    $seenTips[$tipKey] = true;
                    $tips[] = [
                        'id' => $doc->id(),
                        'source_collection' => $collectionName,
                        'landmark_id' => $tipLandmarkId,
                        'landmark_name' => (string) ($data['landmark_name'] ?? $data['landmarkName'] ?? ''),
                        'content' => (string) ($data['content'] ?? $data['message'] ?? ''),
                        'title' => $title,
                        'type' => (string) ($data['type'] ?? ''),
                        'submitted_by' => $submittedBy,
                        'submitted_email' => $submittedEmail,
                        'status' => $status,
                        'review_note' => (string) ($data['review_note'] ?? $data['reviewNote'] ?? ''),
                        'reviewed_by' => (string) ($data['reviewed_by'] ?? $data['reviewedBy'] ?? ''),
                        'created_at' => $this->formatDate($createdAtRaw),
                        'reviewed_at' => $this->formatDate($reviewedAtRaw),
                        'created_sort' => $this->normalizeTimestamp($createdAtRaw),
                    ];
                }
            }
        }

        usort($tips, function (array $a, array $b): int {
            return $b['created_sort'] <=> $a['created_sort'];
        });

        $tips = array_map(function (array $tip): array {
            unset($tip['created_sort']);
            return $tip;
        }, $tips);

        Log::info('Timing curator page segment', [
            'segment' => 'tipsForLandmark',
            'landmark_id' => $landmarkId,
            'status' => $statusFilter,
            'records' => count($tips),
            'duration_ms' => (int) round((microtime(true) - $start) * 1000),
        ]);

        return $tips;
    }

    public function review(Request $request, string $tipId)
    {
        $payload = $request->validate([
            'decision' => 'required|in:accepted,rejected',
            'review_note' => 'nullable|string|max:500',
            'source_collection' => 'nullable|in:' . FirestoreTipCollections::validationInRule(),
            'page' => 'nullable|integer|min:1',
            'status_filter' => 'nullable|in:all,pending,accepted,rejected',
            'return_to_landmark' => 'nullable|boolean',
        ]);

        $collection = $payload['source_collection'] ?? 'crowdsourced_tips';
        $tipRef = $this->firestore()->collection($collection)->document($tipId);
        $tipDoc = $tipRef->snapshot();
        if (!$tipDoc->exists()) {
            return redirect()->route('curators.tips.index')->with('error', 'Tip not found.');
        }

        $tipData = $tipDoc->data();
        $tipLm = FirestoreTipCollections::landmarkIdFromData($tipData);
        if ($tipLm === '' || $tipLm !== $this->assignedLandmarkId()) {
            abort(403);
        }

        $decision = $payload['decision'];
        $tipRef->set([
            'status' => $decision,
            'review_note' => trim((string) ($payload['review_note'] ?? '')),
            'reviewNote' => trim((string) ($payload['review_note'] ?? '')),
            'reviewed_by' => Session::get('email'),
            'reviewedBy' => Session::get('email'),
            'reviewed_at' => now()->toISOString(),
            'reviewedAt' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
            'updatedAt' => now()->toISOString(),
        ], ['merge' => true]);

        foreach (['all', 'pending', 'accepted', 'rejected'] as $status) {
            Cache::forget('curator:tips:'.$tipLm.':'.$status);
        }

        $redirect = ! empty($payload['return_to_landmark'])
            ? redirect()->route('landmarks.show', $tipLm)
            : redirect()->route('curators.tips.index', [
                'page' => $payload['page'] ?? 1,
                'status' => $payload['status_filter'] ?? 'pending',
            ]);

        return $redirect->with('success', 'Tip has been '.$decision.'.');
    }

    private function assignedLandmarkId(): string
    {
        return trim((string) Session::get('assigned_landmark_id', ''));
    }

    private function formatDate($value): string
    {
        if (!$value) {
            return '-';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('M d, Y h:i A');
        }

        if (is_object($value) && method_exists($value, 'get')) {
            try {
                $dt = $value->get();
                if ($dt instanceof \DateTimeInterface) {
                    return $dt->format('M d, Y h:i A');
                }
            } catch (\Throwable $e) {
                return '-';
            }
        }

        try {
            return \Carbon\Carbon::parse((string) $value)->format('M d, Y h:i A');
        } catch (\Throwable $e) {
            return '-';
        }
    }

    private function normalizeTimestamp($value): int
    {
        if (!$value) {
            return 0;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }

        if (is_object($value) && method_exists($value, 'get')) {
            try {
                $dt = $value->get();
                if ($dt instanceof \DateTimeInterface) {
                    return $dt->getTimestamp();
                }
            } catch (\Throwable $e) {
                return 0;
            }
        }

        try {
            return \Carbon\Carbon::parse((string) $value)->getTimestamp();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
