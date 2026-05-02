<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use App\Support\CuratorAssignedLandmark;
use App\Support\QrResolveUrl;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder as BaconQrEncoder;
use Google\Cloud\Firestore\FieldValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QrController extends Controller
{
    public function __construct(private FirebaseService $firebase)
    {
        // Only curators/admins should access this in routes via middleware.
    }

    private function fs()
    {
        return $this->firebase->firestore();
    }

    public function index(Request $request)
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
            // Hide landmark system-generated QR entries from manager list.
            if (preg_match('/^LM-[a-f0-9]{6}$/i', $code)) {
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
            'landmark_id' => 'required|string',
            'format' => 'nullable|in:png,svg',
        ]);

        $code = trim($data['code']);
        $landmarkId = $data['landmark_id'];
        $format = $data['format'] ?? 'png';

        $existing = $this->fs()->collection('qr_codes')->where('code', '==', $code)->limit(1)->documents();
        foreach ($existing as $ex) {
            if ($ex->exists()) {
                return back()->withErrors(['error' => 'QR code already exists. Choose another value.'])->withInput();
            }
        }

        $lm = $this->fs()->collection('landmarks')->document($landmarkId)->snapshot();
        if (! $lm->exists()) {
            return back()->withErrors(['error' => 'Selected landmark does not exist.'])->withInput();
        }

        if (Session::get('role') === 'curator') {
            CuratorAssignedLandmark::assertMatches($landmarkId);
        }

        $qrRef = $this->fs()->collection('qr_codes')->add([
            'code' => $code,
            'landmark_id' => $landmarkId,
            'is_auto' => true,
            'created_at' => FieldValue::serverTimestamp(),
        ]);

        $saved = $this->generateQrImage($code, $format);

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

        $this->generateQrImage($code, 'png');

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

        if ($request->wantsJson()) {
            return response()->json($result['json']);
        }

        $landmark = $result['landmark_raw'];
        $lat = isset($landmark['latitude']) && is_numeric($landmark['latitude']) ? (float) $landmark['latitude'] : null;
        $lng = isset($landmark['longitude']) && is_numeric($landmark['longitude']) ? (float) $landmark['longitude'] : null;

        return response()->view('qr.landmark', [
            'payload' => $result['json'],
            'landmark' => $landmark,
            'videoEmbedUrl' => $this->videoEmbedUrl((string) ($landmark['video_url'] ?? '')),
            'mapUrl' => ($lat !== null && $lng !== null)
                ? 'https://www.openstreetmap.org/?mlat='.$lat.'&mlon='.$lng.'#map=16/'.$lat.'/'.$lng
                : null,
        ]);
    }

    /**
     * @return array{ok: true, json: array, landmark_raw: array}|array{ok: false, status: int, json: array}
     */
    private function lookupQrResolution(string $code): array
    {
        $normalizedCode = $this->normalizeScannedCode($code);

        $qrDocs = $this->fs()->collection('qr_codes')
            ->where('code', '==', $normalizedCode)
            ->limit(1)
            ->documents();

        $qrDoc = null;
        foreach ($qrDocs as $doc) {
            if ($doc->exists()) {
                $qrDoc = $doc;
                break;
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
        $landmarkId = (string) ($qrData['landmark_id'] ?? '');
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

        return [
            'ok' => true,
            'landmark_raw' => $landmark,
            'json' => [
                'qr_code' => [
                    'id' => $qrDoc->id(),
                    'code' => (string) ($qrData['code'] ?? $normalizedCode),
                    'landmark_id' => $landmarkId,
                ],
                'landmark' => [
                    'id' => $landmarkId,
                    'name' => (string) ($landmark['name'] ?? 'Untitled'),
                    'description' => (string) ($landmark['description'] ?? ''),
                    'category' => (string) ($landmark['category'] ?? ''),
                    'latitude' => isset($landmark['latitude']) && is_numeric($landmark['latitude']) ? (float) $landmark['latitude'] : null,
                    'longitude' => isset($landmark['longitude']) && is_numeric($landmark['longitude']) ? (float) $landmark['longitude'] : null,
                    'video_url' => (string) ($landmark['video_url'] ?? ''),
                ],
            ],
        ];
    }

    private function videoEmbedUrl(string $videoUrl): string
    {
        if ($videoUrl === '') {
            return '';
        }
        if (Str::contains($videoUrl, 'youtube.com/watch')) {
            return str_replace('watch?v=', 'embed/', $videoUrl);
        }
        if (Str::contains($videoUrl, 'youtu.be/')) {
            $id = Str::before(Str::after($videoUrl, 'youtu.be/'), '?');

            return 'https://www.youtube.com/embed/'.$id;
        }

        return '';
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
        if (Session::get('role') === 'curator') {
            CuratorAssignedLandmark::assertMatches($landmarkId);
        }

        $firestore = app(\App\Services\FirebaseService::class)->firestore();

        // find QR linked to this landmark
        $snap = $firestore->collection('qr_codes')
            ->where('landmark_id', '==', $landmarkId)
            ->limit(1)
            ->documents();

        $qrDoc = null;
        foreach ($snap as $doc) {
            if ($doc->exists()) {
                $qrDoc = $doc;
                break;
            }
        }

        if (! $qrDoc) {
            return back()->with('error', 'No QR record found for this landmark.');
        }

        $code = $qrDoc->data()['code'] ?? null;
        if (! $code) {
            return back()->with('error', 'QR record has no code.');
        }

        $this->generateQrImage($code, 'png');

        $path = "qrcodes/{$code}.png";
        if (! Storage::disk('public')->exists($path)) {
            return back()->with('error', 'QR image not found.');
        }

        return response()->download(storage_path("app/public/{$path}"));
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
