<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use App\Services\LandmarkEngagement;
use App\Services\QrCodeImageStorage;
use App\Support\CuratorAssignedLandmark;
use App\Support\QrResolveUrl;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder as BaconQrEncoder;
use Google\Cloud\Firestore\FieldValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class QrController extends Controller
{
    public function __construct(private FirebaseService $firebase, private LandmarkEngagement $engagement)
    {
        // Only curators/admins should access this in routes via middleware.
    }

    private function fs()
    {
        return $this->firebase->firestore();
    }

    public function index()
    {
        $accessibleLandmarkIds = Session::get('role') === 'curator'
            ? CuratorAssignedLandmark::browseableIds()
            : null;

        $qrDocs = $this->fs()->collection('qr_codes')->orderBy('code')->documents();
        $qrs = [];
        foreach ($qrDocs as $doc) {
            if (! $doc->exists()) {
                continue;
            }
            $d = $doc->data();
            $lmId = (string) ($d['landmark_id'] ?? '');
            if ($accessibleLandmarkIds !== null && ! in_array($lmId, $accessibleLandmarkIds, true)) {
                continue;
            }

            $code = (string) ($d['code'] ?? '');
            if ($d['is_landmark_join_code'] ?? false) {
                continue;
            }

            $imagePath = (string) ($d['image_path'] ?? '');
            if ($imagePath === '' || ! QrCodeImageStorage::exists($imagePath)) {
                $imagePath = $this->generateQrImage($code, 'png', $lmId) ?? $imagePath;
                if ($imagePath !== '') {
                    $this->fs()->collection('qr_codes')->document($doc->id())->set([
                        'image_path' => $imagePath,
                    ], ['merge' => true]);
                }
            }
            $openUrl = $imagePath !== '' && QrCodeImageStorage::exists($imagePath)
                ? QrCodeImageStorage::url($imagePath)
                : route('curators.qr.view', $doc->id());

            $qrs[] = [
                'id' => $doc->id(),
                'code' => $code,
                'landmark_id' => $d['landmark_id'] ?? '',
                'created_at' => $d['created_at'] ?? null,
                'download_url' => route('curators.qr.download', $doc->id()),
                'preview_url' => $openUrl,
                'encoded_scan_url' => QrResolveUrl::forCode($code),
            ];
        }

        $lmSnap = $this->fs()->collection('landmarks')->orderBy('name')->documents();
        $landmarks = [];
        foreach ($lmSnap as $lm) {
            if (! $lm->exists()) {
                continue;
            }
            if ($accessibleLandmarkIds !== null && ! in_array($lm->id(), $accessibleLandmarkIds, true)) {
                continue;
            }
            $landmarks[] = [
                'id' => $lm->id(),
                'name' => $lm['name'] ?? 'Untitled',
            ];
        }

        return view('curators.qr.index', compact('qrs', 'landmarks'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:120',
            'landmark_id' => 'nullable|string',
            'format' => 'nullable|in:png,svg',
        ]);

        $code = trim($data['code']);
        $landmarkId = Session::get('role') === 'curator'
            ? (CuratorAssignedLandmark::id() ?? '')
            : trim((string) ($data['landmark_id'] ?? ''));
        $format = $data['format'] ?? 'png';

        if ($landmarkId === '') {
            return back()->withErrors(['error' => 'Site is required.'])->withInput();
        }

        $lm = $this->fs()->collection('landmarks')->document($landmarkId)->snapshot();
        if (! $lm->exists()) {
            return back()->withErrors(['error' => 'Selected landmark does not exist.'])->withInput();
        }

        if (Session::get('role') === 'curator') {
            CuratorAssignedLandmark::assertMatches($landmarkId);
        }

        $existingDocId = null;
        $existing = $this->fs()->collection('qr_codes')->where('code', '==', $code)->limit(1)->documents();
        foreach ($existing as $ex) {
            if (! $ex->exists()) {
                continue;
            }
            if ((string) ($ex->data()['landmark_id'] ?? '') !== $landmarkId) {
                return back()->withErrors(['error' => 'QR code already exists. Choose another value.'])->withInput();
            }
            $existingDocId = $ex->id();
        }

        if ($existingDocId === null) {
            $sameLandmark = $this->fs()->collection('qr_codes')
                ->where('landmark_id', '==', $landmarkId)
                ->limit(1)
                ->documents();
            foreach ($sameLandmark as $ex) {
                if ($ex->exists()) {
                    $existingDocId = $ex->id();
                    break;
                }
            }
        }

        $payload = [
            'code' => $code,
            'landmark_id' => $landmarkId,
            'is_auto' => true,
            'created_at' => FieldValue::serverTimestamp(),
        ];

        if ($existingDocId !== null) {
            unset($payload['created_at']);
            $docRef = $this->fs()->collection('qr_codes')->document($existingDocId);
            $docRef->set($payload, ['merge' => true]);
        } else {
            $docRef = $this->fs()->collection('qr_codes')->add($payload);
        }

        $imagePath = $this->generateQrImage($code, 'png', $landmarkId);
        if ($imagePath !== null) {
            $docRef->set(['image_path' => $imagePath], ['merge' => true]);
        }
        $saved = $imagePath !== null;
        $this->fs()->collection('logs')->add([
            'email' => (string) Session::get('email', ''),
            'role' => (string) Session::get('role', ''),
            'action' => 'Generated QR code: '.$code,
            'qr_code' => $code,
            'landmark_id' => $landmarkId,
            'landmark_name' => (string) ($lm->data()['name'] ?? ''),
            'timestamp' => now()->toISOString(),
        ]);

        return redirect()->route('curators.qr')
            ->with('success', 'QR mapping created'.($saved ? ' and image generated.' : '.'));
    }

    public function destroy(string $id)
    {
        $docRef = $this->fs()->collection('qr_codes')->document($id);
        $doc = $docRef->snapshot();
        $deletedCode = '';

        if (Session::get('role') === 'curator' && $doc->exists()) {
            $linkedLm = (string) ($doc->data()['landmark_id'] ?? '');
            CuratorAssignedLandmark::assertMatches($linkedLm);
        }

        if ($doc->exists()) {
            $code = (string) ($doc['code'] ?? '');
            $data = $doc->data();
            $landmarkId = (string) ($data['landmark_id'] ?? '');
            $deletedCode = $code;

            $docRef->delete();

            try {
                QrCodeImageStorage::deletePath((string) ($data['image_path'] ?? ''));
                QrCodeImageStorage::deleteFor($landmarkId, $code);
                QrCodeImageStorage::deleteFor($code, $landmarkId);
            } catch (\Throwable $e) {
            }
        }

        $message = $deletedCode !== ''
            ? 'QR mapping deleted "'.$deletedCode.'".'
            : 'QR mapping deleted.';

        return back()->with('success', $message);
    }

    public function download(string $id)
    {
        $doc = $this->fs()->collection('qr_codes')->document($id)->snapshot();
        if (! $doc->exists()) {
            abort(404);
        }

        if (Session::get('role') === 'curator') {
            $linkedLm = (string) ($doc->data()['landmark_id'] ?? '');
            CuratorAssignedLandmark::assertMatches($linkedLm);
        }

        $code = (string) ($doc['code'] ?? '');
        if ($code === '') {
            abort(404);
        }
        $landmarkId = (string) ($doc->data()['landmark_id'] ?? '');

        $data = $doc->data();
        $imagePath = $this->ensureQrImage(
            $code,
            (string) ($data['landmark_id'] ?? ''),
            (string) ($data['image_path'] ?? ''),
            $this->fs()->collection('qr_codes')->document($id)
        );
        $this->fs()->collection('logs')->add([
            'email' => (string) Session::get('email', ''),
            'role' => (string) Session::get('role', ''),
            'action' => 'Downloaded QR code: '.$code,
            'qr_code' => $code,
            'landmark_id' => $landmarkId,
            'timestamp' => now()->toISOString(),
        ]);

        if ($imagePath !== null && QrCodeImageStorage::exists($imagePath)) {
            return response()->download(QrCodeImageStorage::absolutePath($imagePath), basename($imagePath));
        }

        abort(404, 'No QR code image could be generated.');
    }

    public function view(string $id)
    {
        $doc = $this->fs()->collection('qr_codes')->document($id)->snapshot();
        if (! $doc->exists()) {
            abort(404);
        }

        if (Session::get('role') === 'curator') {
            $linkedLm = (string) ($doc->data()['landmark_id'] ?? '');
            CuratorAssignedLandmark::assertMatches($linkedLm);
        }

        $code = (string) ($doc['code'] ?? '');
        if ($code === '') {
            abort(404);
        }

        // Stored PNG keeps the URL from when it was first saved; re-encode so preview matches QrResolveUrl / .env now.
        $data = $doc->data();
        $imagePath = $this->ensureQrImage(
            $code,
            (string) ($data['landmark_id'] ?? ''),
            (string) ($data['image_path'] ?? ''),
            $this->fs()->collection('qr_codes')->document($id)
        );

        if ($imagePath !== null && QrCodeImageStorage::exists($imagePath)) {
            return response(QrCodeImageStorage::get($imagePath), 200, [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'inline; filename="'.basename($imagePath).'"',
            ]);
        }

        abort(404, 'No QR code image could be generated.');
    }

    public function resolve(Request $request, string $code)
    {
        $result = $this->lookupQrResolution($code);

        if (! $result['ok']) {
            if ($request->wantsJson()) {
                return response()->json($result['json'], $result['status']);
            }

            return response()->view('qr.error', [
                'message' => (string) ($result['json']['message'] ?? 'Something went wrong.'),
                'code' => (string) ($result['json']['code'] ?? ''),
            ], $result['status']);
        }

        $landmark = $result['landmark_raw'];
        try {
            $landmarkId = (string) ($result['json']['landmark']['id'] ?? '');
            $this->engagement->record($request, $landmarkId, 'qr_scan', [
                'qr_code' => (string) ($result['json']['qr_code']['code'] ?? ''),
            ]);
            $this->engagement->record($request, $landmarkId, 'landmark_view', ['source' => 'qr']);
        } catch (\Throwable $e) {
            report($e);
        }

        if ($request->wantsJson()) {
            return response()->json($result['json']);
        }

        $lat = isset($landmark['latitude']) && is_numeric($landmark['latitude']) ? (float) $landmark['latitude'] : null;
        $lng = isset($landmark['longitude']) && is_numeric($landmark['longitude']) ? (float) $landmark['longitude'] : null;

        return response()->view('qr.landmark', [
            'payload' => $result['json'],
            'landmark' => $landmark,
            'mapUrl' => ($lat !== null && $lng !== null)
                ? 'https://www.openstreetmap.org/?mlat='.$lat.'&mlon='.$lng.'#map=16/'.$lat.'/'.$lng
                : null,
            'mapboxToken' => config('services.mapbox.token'),
        ]);
    }

    /**
     * @return array{ok: true, json: array, landmark_raw: array}|array{ok: false, status: int, json: array}
     */
    private function lookupQrResolution(string $code): array
    {
        $normalizedCode = $this->normalizeScannedCode($code);

        $qrDoc = null;
        $qrDocs = $this->fs()->collection('qrcodes')
            ->where('code', '==', $normalizedCode)
            ->limit(1)
            ->documents();

        foreach ($qrDocs as $doc) {
            if ($doc->exists()) {
                $qrDoc = $doc;
                break;
            }
        }

        if (! $qrDoc) {
            $doc = $this->fs()->collection('qrcodes')->document($normalizedCode)->snapshot();
            if ($doc->exists()) {
                $qrDoc = $doc;
            }
        }

        if (! $qrDoc) {
            return [
                'ok' => false,
                'status' => 404,
                'json' => [
                    'message' => 'QR code not found.',
                    'code' => $normalizedCode,
                ],
            ];
        }

        $qrData = $qrDoc->data();
        $landmarkId = (string) ($qrData['landmarkId'] ?? '');
        if ($landmarkId === '') {
            return [
                'ok' => false,
                'status' => 422,
                'json' => [
                    'message' => 'QR code is not linked to a landmark.',
                    'code' => $normalizedCode,
                ],
            ];
        }

        $landmarkDoc = $this->fs()->collection('landmarks')->document($landmarkId)->snapshot();
        if (! $landmarkDoc->exists()) {
            return [
                'ok' => false,
                'status' => 404,
                'json' => [
                    'message' => 'Linked landmark not found.',
                    'code' => $normalizedCode,
                    'landmark_id' => $landmarkId,
                ],
            ];
        }

        $landmark = $landmarkDoc->data();
        $activation = strtolower((string) ($landmark['activation_status'] ?? 'active'));
        if (! \App\Support\LandmarkActivation::isBrowsable($activation)) {
            return [
                'ok' => false,
                'status' => 403,
                'json' => [
                    'message' => 'This landmark is not yet available to the public.',
                    'code' => $normalizedCode,
                ],
            ];
        }

        return [
            'ok' => true,
            'landmark_raw' => $landmark,
            'json' => [
                'qr_code' => [
                    'id' => $qrDoc->id(),
                    'code' => (string) ($qrData['landmarkCode'] ?? $normalizedCode),
                    'landmark_id' => $landmarkId,
                    'url' => (string) ($qrData['qrUrl'] ?? QrResolveUrl::forCode($normalizedCode)),
                ],
                'landmark' => [
                    'id' => $landmarkId,
                    'name' => (string) ($landmark['name'] ?? 'Untitled'),
                    'description' => (string) ($landmark['description'] ?? ''),
                    'category' => (string) ($landmark['category'] ?? ''),
                    'latitude' => isset($landmark['latitude']) && is_numeric($landmark['latitude']) ? (float) $landmark['latitude'] : null,
                    'longitude' => isset($landmark['longitude']) && is_numeric($landmark['longitude']) ? (float) $landmark['longitude'] : null,
                ],
            ],
        ];
    }

    /**
     * Attempt to generate and save a QR PNG to storage/app/public/qrcodes/{identifier}.png.
     * Returns the relative storage path on success.
     */
    private function generateQrImage(string $code, string $format = 'png', string $landmarkId = ''): ?string
    {
        $url = QrResolveUrl::forCode($code);
        $path = QrCodeImageStorage::pathFor($landmarkId, $code);

        try {
            if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
                try {
                    $qr = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                        ->size(600)->margin(1)
                        ->generate($url);

                    if (is_string($qr) && str_starts_with($qr, "\x89PNG\r\n\x1a\n")) {
                        QrCodeImageStorage::putPng($path, $qr);

                        return $path;
                    }
                } catch (\Throwable $e) {
                    // PNG may fail on some environments. Try GD-based fallback first.
                }
            }

            $gdPng = $this->generateQrPngWithGd($url);
            if ($gdPng !== false) {
                QrCodeImageStorage::putPng($path, $gdPng);

                return $path;
            }

            return null;

        } catch (\Throwable $e) {
            return null;
        }
    }

    public function downloadByLandmark(string $landmarkId)
    {
        $qr = $this->qrPngForLandmark($landmarkId);
        if ($qr === null) {
            abort(404, 'No QR code has been generated for this landmark.');
        }
        $this->fs()->collection('logs')->add([
            'email' => (string) Session::get('email', ''),
            'role' => (string) Session::get('role', ''),
            'action' => 'Downloaded QR code: '.pathinfo($qr['filename'], PATHINFO_FILENAME),
            'qr_code' => pathinfo($qr['filename'], PATHINFO_FILENAME),
            'landmark_id' => $landmarkId,
            'timestamp' => now()->toISOString(),
        ]);

        return response($qr['png'], 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="'.$qr['filename'].'"',
            'Content-Length' => (string) strlen($qr['png']),
            'Cache-Control' => 'no-store, max-age=0',
        ]);
    }

    /**
     * @return array{png: string, filename: string}|null
     */
    private function qrPngForLandmark(string $landmarkId): ?array
    {
        $qr = $this->qrcodeDocumentForLandmark($landmarkId);
        if ($qr === null) {
            return null;
        }

        $imagePath = $this->ensureQrImage($qr['landmarkCode'], $landmarkId, (string) ($qr['imagePath'] ?? ''));
        if ($imagePath === null || ! QrCodeImageStorage::exists($imagePath)) {
            return null;
        }

        $png = QrCodeImageStorage::get($imagePath);

        return [
            'png' => $png,
            'filename' => basename($imagePath),
        ];
    }

    /**
     * @return array{landmarkCode: string, qrUrl: string, imagePath: string}|null
     */
    private function qrcodeDocumentForLandmark(string $landmarkId): ?array
    {
        if (Session::get('role') === 'curator') {
            CuratorAssignedLandmark::assertMatches($landmarkId);
        }

        $doc = $this->fs()->collection('landmarks')->document($landmarkId)->snapshot();
        if (! $doc->exists()) {
            return null;
        }

        $code = $this->landmarkCode($doc->data());
        if ($code === null) {
            return null;
        }

        $qrUrl = QrResolveUrl::forCode($code);
        $imagePath = QrCodeImageStorage::pathFor($landmarkId, $code);
        $docRef = $this->fs()->collection('qrcodes')->document($code);
        $qrDoc = $docRef->snapshot();
        $payload = [
            'code' => $code,
            'landmarkId' => $landmarkId,
            'landmarkCode' => $code,
            'landmarkName' => (string) ($doc->data()['name'] ?? 'Untitled'),
            'qrUrl' => $qrUrl,
            'image_path' => $imagePath,
            'status' => 'active',
        ];

        if (! $qrDoc->exists() || ! array_key_exists('createdAt', $qrDoc->data())) {
            $payload['createdAt'] = FieldValue::serverTimestamp();
        }

        $docRef->set($payload, ['merge' => true]);

        return [
            'landmarkCode' => $code,
            'qrUrl' => $qrUrl,
            'imagePath' => $imagePath,
        ];
    }

    private function ensureQrImage(string $code, string $landmarkId = '', string $storedPath = '', mixed $docRef = null): ?string
    {
        $path = $storedPath !== '' ? $storedPath : QrCodeImageStorage::pathFor($landmarkId, $code);
        $generatedPath = $this->generateQrImage($code, 'png', $landmarkId);
        if ($generatedPath !== null) {
            $path = $generatedPath;
        }

        if (! QrCodeImageStorage::exists($path)) {
            return null;
        }

        if ($docRef !== null) {
            try {
                $docRef->set(['image_path' => $path], ['merge' => true]);
            } catch (\Throwable $e) {
            }
        }

        return $path;
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

    private function generateQrPngForValue(string $value): string|false
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
                // PNG generation can fail without Imagick; GD fallback below still returns PNG.
            }
        }

        return $this->generateQrPngWithGd($value);
    }

    private function generateQrPngWithGd(string $url): string|false
    {
        if (! extension_loaded('gd')) {
            return false;
        }

        try {
            $ecLevel = ErrorCorrectionLevel::M();
            $qrCode = BaconQrEncoder::encode($url, $ecLevel);
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
            $data = ob_get_clean();
            imagedestroy($img);

            return ($data !== '' && $data !== false) ? $data : false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function normalizeScannedCode(string $value): string
    {
        $normalized = trim(urldecode($value));
        if ($normalized === '') {
            return '';
        }

        if (filter_var($normalized, FILTER_VALIDATE_URL)) {
            $path = parse_url($normalized, PHP_URL_PATH);
            if (is_string($path) && $path !== '') {
                $segments = array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));
                if (! empty($segments)) {
                    $normalized = (string) end($segments);
                }
            }
        }

        if (str_contains($normalized, '?')) {
            $normalized = (string) strtok($normalized, '?');
        }

        return trim($normalized);
    }
}
