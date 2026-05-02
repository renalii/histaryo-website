<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use App\Support\CuratorAssignedLandmark;
use App\Services\FirebaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class TipReviewController extends Controller
{
    protected $firestore;
    private array $tipCollections = ['crowdsourced_tips', 'tips', 'user_tips'];

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firestore = $firebaseService->firestore();
    }

    public function index(Request $request)
    {
        $statusFilter = strtolower((string) $request->query('status', 'pending'));
        if (!in_array($statusFilter, ['all', 'pending', 'accepted', 'rejected'], true)) {
            $statusFilter = 'pending';
        }

        $tips = $this->fetchTips($statusFilter);
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

    public function fetchData(Request $request)
    {
        $statusFilter = strtolower((string) $request->query('status', 'all'));
        if (!in_array($statusFilter, ['all', 'pending', 'accepted', 'rejected'], true)) {
            $statusFilter = 'all';
        }

        return response()->json([
            'tips' => $this->fetchTips($statusFilter),
        ]);
    }

    private function fetchTips(string $statusFilter = 'all'): array
    {
        $writableSet = $this->landmarkIdKeySet(CuratorAssignedLandmark::writableIds());
        $browseableSet = $this->landmarkIdKeySet(CuratorAssignedLandmark::browseableIds());

        $tips = [];
        foreach ($this->tipCollections as $collectionName) {
            $tipsSnapshot = $this->firestore->collection($collectionName)->documents();

            foreach ($tipsSnapshot as $doc) {
                if (!$doc->exists()) {
                    continue;
                }

                $data = $doc->data();
                $status = strtolower((string) ($data['status'] ?? 'pending'));

                if ($status === '') {
                    $status = 'pending';
                }

                $status = in_array($status, ['accepted', 'rejected', 'pending'], true) ? $status : 'pending';
                if ($statusFilter !== 'all' && $status !== $statusFilter) {
                    continue;
                }

                $tipLandmarkId = trim((string) ($data['landmark_id'] ?? $data['landmarkId'] ?? ''));

                // crowdsourced_tips: show any tip tied to an active (browseable) landmark.
                // tips / user_tips: still scoped to landmarks this curator may edit.
                $scopeSet = $collectionName === 'crowdsourced_tips' ? $browseableSet : $writableSet;
                if ($scopeSet !== []) {
                    if ($tipLandmarkId === '' || ! isset($scopeSet[$tipLandmarkId])) {
                        continue;
                    }
                }

                $canModerate = $tipLandmarkId !== '' && isset($writableSet[$tipLandmarkId]);

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

                $tips[] = [
                    'id' => $doc->id(),
                    'source_collection' => $collectionName,
                    'landmark_id' => $tipLandmarkId,
                    'landmark_name' => (string) ($data['landmark_name'] ?? $data['landmarkName'] ?? ''),
                    'content' => (string) ($data['content'] ?? $data['message'] ?? ''),
                    'title' => $title,
                    'can_moderate' => $canModerate,
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

        usort($tips, function (array $a, array $b): int {
            return $b['created_sort'] <=> $a['created_sort'];
        });

        $tips = array_map(function (array $tip): array {
            unset($tip['created_sort']);
            return $tip;
        }, $tips);

        return $tips;
    }

    public function review(Request $request, string $tipId)
    {
        $payload = $request->validate([
            'decision' => 'required|in:accepted,rejected',
            'review_note' => 'nullable|string|max:500',
            'source_collection' => 'nullable|in:crowdsourced_tips,tips,user_tips',
            'page' => 'nullable|integer|min:1',
            'status_filter' => 'nullable|in:all,pending,accepted,rejected',
        ]);

        $collection = $payload['source_collection'] ?? 'crowdsourced_tips';
        $tipRef = $this->firestore->collection($collection)->document($tipId);
        $tipDoc = $tipRef->snapshot();
        if (!$tipDoc->exists()) {
            return redirect()->route('curators.tips.index')->with('error', 'Tip not found.');
        }

        $tipData = $tipDoc->data();
        $tipLm = trim((string) ($tipData['landmark_id'] ?? $tipData['landmarkId'] ?? ''));
        if (! CuratorAssignedLandmark::canAccess($tipLm)) {
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

        $this->firestore->collection('logs')->add([
            'email' => Session::get('email'),
            'action' => ucfirst($decision) . ' a user tip',
            'timestamp' => now()->toISOString(),
        ]);

        return redirect()->route('curators.tips.index', [
                'page' => $payload['page'] ?? 1,
                'status' => $payload['status_filter'] ?? 'pending',
            ])
            ->with('success', 'Tip has been ' . $decision . '.');
    }

    /**
     * @param  list<string>  $ids
     * @return array<string, true>
     */
    private function landmarkIdKeySet(array $ids): array
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
