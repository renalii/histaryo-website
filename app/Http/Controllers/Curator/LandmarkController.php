<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use App\Support\CuratorAssignedLandmark;
use App\Support\QrResolveUrl;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder as BaconQrEncoder;
use Google\Cloud\Firestore\FieldValue;
use Illuminate\Http\Request;

class LandmarkController extends Controller
{
    protected $firestore;

    public function __construct(
        protected FirebaseService $firebaseService,
        protected TipReviewController $tipReviewController,
    )
    {
        $this->firestore = $firebaseService->firestore();
    }

    public function map(Request $request)
    {
        $landmarks = [];

        foreach (CuratorAssignedLandmark::browseableIds() as $lid) {
            $doc = $this->firestore->collection('landmarks')->document($lid)->snapshot();
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

        $snapshot = $this->firestore->collection('landmarks')->document($id)->snapshot();
        if (! $snapshot->exists()) {
            abort(404);
        }
        $data = $snapshot->data();

        return view('curators.landmarks.index', [
            'landmark' => $snapshot,
            'siteManagerAttribution' => $this->resolveSiteManagerAttributionLabel($data),
            'mapboxToken' => config('services.mapbox.token'),
            'landmarkTips' => $this->tipReviewController->tipsForLandmark($id),
            'qrPreview' => $this->qrPreviewForLandmark($id, $data),
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

        $prof = $this->firebaseService->userDocument($managerUid, 'site_manager')->snapshot();
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
    }

    /**
     * @return array{base64: string, filename: string}|null
     */
    private function qrPreviewForLandmark(string $landmarkId, array $landmarkData): ?array
    {
        $qr = $this->qrcodeDocumentForLandmark($landmarkId, $landmarkData);
        if ($qr === null) {
            return null;
        }

        $png = $this->generateQrPng($qr['qrUrl']);
        if ($png === null || ! str_starts_with($png, "\x89PNG\r\n\x1a\n")) {
            return null;
        }

        $filenameCode = trim((string) preg_replace('/[^a-zA-Z0-9_-]+/', '-', $qr['landmarkCode']), '-');
        if ($filenameCode === '') {
            $filenameCode = 'landmark-code';
        }

        return [
            'base64' => base64_encode($png),
            'filename' => $filenameCode.'-qr.png',
        ];
    }

    /**
     * @return array{landmarkCode: string, qrUrl: string}|null
     */
    private function qrcodeDocumentForLandmark(string $landmarkId, array $landmarkData): ?array
    {
        $code = $this->landmarkCode($landmarkData);
        if ($code === null) {
            return null;
        }

        $qrUrl = QrResolveUrl::forCode($code);
        $docRef = $this->firestore->collection('qrcodes')->document($code);
        $doc = $docRef->snapshot();
        $payload = [
            'code' => $code,
            'landmarkId' => $landmarkId,
            'landmarkCode' => $code,
            'landmarkName' => (string) ($landmarkData['name'] ?? 'Untitled'),
            'qrUrl' => $qrUrl,
            'status' => 'active',
        ];

        if (! $doc->exists() || ! array_key_exists('createdAt', $doc->data())) {
            $payload['createdAt'] = FieldValue::serverTimestamp();
        }

        $docRef->set($payload, ['merge' => true]);

        return [
            'landmarkCode' => $code,
            'qrUrl' => $qrUrl,
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

    private function generateQrPng(string $value): ?string
    {
        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            try {
                $png = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                    ->size(600)
                    ->margin(1)
                    ->generate($value);

                if (is_string($png) && str_starts_with($png, "\x89PNG\r\n\x1a\n")) {
                    return $png;
                }
            } catch (\Throwable $e) {
                // Some local PHP installs lack Imagick; GD fallback below still returns a real PNG.
            }
        }

        return $this->generateQrPngWithGd($value);
    }

    private function generateQrPngWithGd(string $value): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        try {
            $qrCode = BaconQrEncoder::encode($value, ErrorCorrectionLevel::M());
            $matrix = $qrCode->getMatrix();

            $numCells = $matrix->getWidth();
            $margin = 4;
            $targetPx = 600;
            $cellSize = max(1, (int) floor($targetPx / ($numCells + (2 * $margin))));
            $imgSize = ($numCells + 2 * $margin) * $cellSize;

            $img = imagecreatetruecolor($imgSize, $imgSize);
            $white = imagecolorallocate($img, 255, 255, 255);
            $black = imagecolorallocate($img, 0, 0, 0);

            imagefill($img, 0, 0, $white);

            for ($y = 0; $y < $numCells; $y++) {
                for ($x = 0; $x < $numCells; $x++) {
                    if ($matrix->get($x, $y) !== 0) {
                        $px = ($x + $margin) * $cellSize;
                        $py = ($y + $margin) * $cellSize;
                        imagefilledrectangle(
                            $img,
                            $px,
                            $py,
                            $px + $cellSize - 1,
                            $py + $cellSize - 1,
                            $black
                        );
                    }
                }
            }

            ob_start();
            imagepng($img);
            $png = ob_get_clean();
            imagedestroy($img);

            return is_string($png) && $png !== '' ? $png : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

}
