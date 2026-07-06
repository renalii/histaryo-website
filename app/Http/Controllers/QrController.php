<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use App\Services\LandmarkEngagement;
use App\Support\CuratorAssignedLandmark;
use App\Support\QrResolveUrl;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder as BaconQrEncoder;
use Google\Cloud\Firestore\FieldValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

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

            $openUrl = route('curators.qr.view', $doc->id());

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

        $existing = $this->fs()->collection('qr_codes')->where('code', '==', $code)->limit(1)->documents();
        foreach ($existing as $ex) {
            if ($ex->exists()) {
                return back()->withErrors(['error' => 'QR code already exists. Choose another value.'])->withInput();
            }
        }

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

        $this->fs()->collection('qr_codes')->add([
            'code' => $code,
            'landmark_id' => $landmarkId,
            'is_auto' => true,
            'created_at' => FieldValue::serverTimestamp(),
        ]);

        $saved = $this->generateQrImage($code, $format);
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
            $deletedCode = $code;

            $docRef->delete();

            foreach (['png', 'svg'] as $ext) {
                $path = "qrcodes/{$code}.{$ext}";
                try {
                    Storage::disk('public')->delete($path);
                } catch (\Throwable $e) {
                }
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

        $this->generateQrImage($code, 'png');
        $this->fs()->collection('logs')->add([
            'email' => (string) Session::get('email', ''),
            'role' => (string) Session::get('role', ''),
            'action' => 'Downloaded QR code: '.$code,
            'qr_code' => $code,
            'landmark_id' => $landmarkId,
            'timestamp' => now()->toISOString(),
        ]);

        $pngPath = "qrcodes/{$code}.png";
        if (Storage::disk('public')->exists($pngPath)) {
            return response()->download(Storage::disk('public')->path($pngPath), "{$code}.png");
        }

        $url = QrResolveUrl::forCode($code);
        $svg = $this->makeQrSvg($url);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="'.$code.'.svg"',
        ]);
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

        $pngPath = "qrcodes/{$code}.png";
        $svgPath = "qrcodes/{$code}.svg";

        // Stored PNG keeps the URL from when it was first saved; re-encode so preview matches QrResolveUrl / .env now.
        $this->generateQrImage($code, 'png');

        if (Storage::disk('public')->exists($pngPath)) {
            return response(Storage::disk('public')->get($pngPath), 200, [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'inline; filename="'.$code.'.png"',
            ]);
        }

        if (Storage::disk('public')->exists($svgPath)) {
            return response(Storage::disk('public')->get($svgPath), 200, [
                'Content-Type' => 'image/svg+xml',
                'Content-Disposition' => 'inline; filename="'.$code.'.svg"',
            ]);
        }

        $svg = $this->makeQrSvg(QrResolveUrl::forCode($code));

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'inline; filename="'.$code.'.svg"',
        ]);
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
     * Attempt to generate and save a QR image to storage/app/public/qrcodes/{code}.{ext}
     * Returns true on success, false otherwise.
     */
    private function generateQrImage(string $code, string $format = 'png'): bool
    {
        $url = QrResolveUrl::forCode($code);
        $dir = 'qrcodes';
        $ext = in_array($format, ['png', 'svg']) ? $format : 'png';
        $path = "{$dir}/{$code}.{$ext}";

        try {

            if (! Storage::disk('public')->exists($dir)) {
                Storage::disk('public')->makeDirectory($dir);
            }

            if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
                try {
                    $qr = \SimpleSoftwareIO\QrCode\Facades\QrCode::format($ext)
                        ->size(600)->margin(1)
                        ->generate($url);

                    Storage::disk('public')->put($path, $qr);

                    return true;
                } catch (\Throwable $e) {
                    // PNG may fail on some environments. Try GD-based fallback first.
                    if ($ext === 'png') {
                        $gdPng = $this->generateQrPngWithGd($url);
                        if ($gdPng !== false) {
                            Storage::disk('public')->put($path, $gdPng);

                            return true;
                        }

                        // Fall back to real scannable SVG instead of placeholder image.
                        $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                            ->size(600)->margin(1)
                            ->generate($url);
                        Storage::disk('public')->put("{$dir}/{$code}.svg", $svg);

                        return true;
                    }
                }
            }

            if ($ext === 'svg') {
                $svg = $this->makeQrSvg($url);
                Storage::disk('public')->put($path, $svg);

                return true;
            }

            return false;

        } catch (\Throwable $e) {
            return false;
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

        $png = $this->generateQrPngForValue($qr['qrUrl']);
        if (! is_string($png) || ! str_starts_with($png, "\x89PNG\r\n\x1a\n")) {
            return null;
        }

        $filenameCode = trim((string) preg_replace('/[^a-zA-Z0-9_-]+/', '-', $qr['landmarkCode']), '-');
        if ($filenameCode === '') {
            $filenameCode = 'landmark-code';
        }

        return [
            'png' => $png,
            'filename' => $filenameCode.'-qr.png',
        ];
    }

    /**
     * @return array{landmarkCode: string, qrUrl: string}|null
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
        $docRef = $this->fs()->collection('qrcodes')->document($code);
        $qrDoc = $docRef->snapshot();
        $payload = [
            'code' => $code,
            'landmarkId' => $landmarkId,
            'landmarkCode' => $code,
            'landmarkName' => (string) ($doc->data()['name'] ?? 'Untitled'),
            'qrUrl' => $qrUrl,
            'status' => 'active',
        ];

        if (! $qrDoc->exists() || ! array_key_exists('createdAt', $qrDoc->data())) {
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

    /**
     * Minimal SVG QR (fallback). For high quality, install simple-qrcode package.
     * NOTE: This is a placeholder; for production-quality PNG/SVG, use the package.
     */
    private function makeQrSvg(string $text): string
    {

        $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        return <<<SVG
                <svg xmlns="http://www.w3.org/2000/svg" width="600" height="600">
                <rect width="100%" height="100%" fill="#ffffff"/>
                <rect x="10" y="10" width="580" height="580" fill="none" stroke="#000" stroke-width="6"/>
                <text x="50%" y="50%" font-family="monospace" font-size="18" text-anchor="middle">
                    {$safe}
                </text>
                <text x="50%" y="570" font-family="monospace" font-size="14" text-anchor="middle" fill="#666">
                    (Install simple-qrcode for scannable codes)
                </text>
                </svg>
                SVG;
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
