<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use App\Services\QrCodeImageStorage;
use App\Support\ArrayDocumentSnapshot;
use App\Support\CuratorAssignedLandmark;
use App\Support\QrResolveUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LandmarkController extends Controller
{
    protected $firestore = null;

    public function __construct(
        protected FirebaseService $firebaseService,
        protected TipReviewController $tipReviewController,
    ) {}

    private function firestore()
    {
        return $this->firestore ??= $this->firebaseService->firestore();
    }

    public function map(Request $request)
    {
        $landmarks = [];

        foreach (CuratorAssignedLandmark::browseableIds() as $lid) {
            $doc = $this->firestore()->collection('landmarks')->document($lid)->snapshot();
            if (! $doc->exists()) {
                continue;
            }
            $data = $doc->data();
            $lat = $data['latitude'] ?? $data['lati'] ?? null;
            $lng = $data['longitude'] ?? $data['longti'] ?? null;

            if (is_numeric($lat) && is_numeric($lng)) {
                $data['latitude'] = (float) $lat;
                $data['longitude'] = (float) $lng;
                unset($data['lati'], $data['longti']);
                $landmarks[] = array_merge($data, ['id' => $doc->id()]);
            }
        }

        $mapboxToken = config('services.mapbox.token');

        return view('curators.landmarks.map', compact('landmarks', 'mapboxToken'));
    }

    public function index(Request $request)
    {
        $assignedId = CuratorAssignedLandmark::id();
        if ($assignedId) {
            return redirect()->route('landmarks.show', $assignedId);
        }

        return view('curators.landmarks.index', [
            'landmark' => null,
            'siteManagerAttribution' => null,
        ]);
    }

    public function show(string $landmark)
    {
        return $this->landmarkDetailView((string) $landmark);
    }

    private function landmarkDetailView(string $id)
    {
        if (! in_array($id, CuratorAssignedLandmark::browseableIds(), true)) {
            abort(403);
        }

        $start = microtime(true);
        $landmark = Cache::remember('curator:landmark-detail:'.$id, now()->addMinutes(5), function () use ($id): ?array {
            $queryStart = microtime(true);
            $snapshot = $this->firestore()->collection('landmarks')->document($id)->snapshot();
            Log::info('Timing Firestore query', [
                'query' => 'curator_landmark.detail_snapshot',
                'landmark_id' => $id,
                'duration_ms' => (int) round((microtime(true) - $queryStart) * 1000),
            ]);

            return $snapshot->exists() ? $snapshot->data() : null;
        });

        if ($landmark === null) {
            abort(404);
        }

        Log::info('Timing curator page', [
            'route' => 'curators.landmarks.show',
            'landmark_id' => $id,
            'duration_ms' => (int) round((microtime(true) - $start) * 1000),
        ]);

        return view('curators.landmarks.index', [
            'landmark' => new ArrayDocumentSnapshot($id, $landmark),
            'siteManagerAttribution' => $this->resolveSiteManagerAttributionLabel($landmark),
            'mapboxToken' => config('services.mapbox.token'),
            'landmarkTips' => $this->tipReviewController->tipsForLandmark($id),
            'qrPreview' => $this->qrPreviewForLandmark($id, $landmark),
        ]);
    }

    public function edit(string $landmark)
    {
        abort(403, 'Curators have read-only access to landmark details.');
    }

    public function update(Request $request, $id)
    {
        abort(403, 'Landmark modifications are restricted to Site Managers.');
    }

    public function destroy(string $id)
    {
        abort(403, 'Landmark deletion is restricted to Site Managers.');
    }

    private function resolveSiteManagerAttributionLabel(array $landmarkData): ?string
    {
        $managerUid = isset($landmarkData['manager_uid']) ? trim((string) $landmarkData['manager_uid']) : '';
        if ($managerUid === '') {
            return null;
        }

        return Cache::remember('curator:site-manager-label:'.$managerUid, now()->addMinutes(10), function () use ($managerUid): ?string {
            $queryStart = microtime(true);
            $prof = $this->firebaseService->userDocument($managerUid, 'site_manager')->snapshot();
            Log::info('Timing Firestore query', [
                'query' => 'curator_landmark.site_manager_profile',
                'manager_uid' => $managerUid,
                'duration_ms' => (int) round((microtime(true) - $queryStart) * 1000),
            ]);

            if (! $prof->exists()) {
                return null;
            }

            $pd = $prof->data();
            $name = trim((string) ($pd['name'] ?? ''));
            if ($name !== '') {
                return $name;
            }

            $email = trim((string) ($pd['email'] ?? ''));

            return $email !== '' ? $email : null;
        });
    }

    /**
     * @return array{base64: string, url: string, downloadUrl: string, filename: string}|null
     */
    private function qrPreviewForLandmark(string $landmarkId, array $landmarkData): ?array
    {
        $qr = $this->qrcodeDocumentForLandmark($landmarkId, $landmarkData);
        if ($qr === null) {
            return null;
        }

        return [
            'base64' => '',
            'url' => route('curators.qr.byLandmark', [
                'landmarkId' => $landmarkId,
                'preview' => 1,
            ]),
            'downloadUrl' => route('curators.qr.byLandmark', [
                'landmarkId' => $landmarkId,
            ]),
            'filename' => basename($qr['imagePath']),
        ];
    }

    /**
     * @return array{landmarkCode: string, qrUrl: string, imagePath: string}|null
     */
    private function qrcodeDocumentForLandmark(string $landmarkId, array $landmarkData): ?array
    {
        $code = $this->landmarkCode($landmarkData);
        if ($code === null) {
            return null;
        }

        $qrUrl = QrResolveUrl::forCode($code);
        $imagePath = QrCodeImageStorage::pathFor($landmarkId, $code);
        return [
            'landmarkCode' => $code,
            'qrUrl' => $qrUrl,
            'imagePath' => $imagePath,
        ];
    }

    private function landmarkCode(array $landmarkData): ?string
    {
        foreach (['code', 'landmarkcode', 'landmark_code', 'landmarkCode', 'qr_code', 'qrCode'] as $field) {
            $code = trim((string) ($landmarkData[$field] ?? ''));
            if ($code !== '') {
                return $code;
            }
        }

        return null;
    }

}
