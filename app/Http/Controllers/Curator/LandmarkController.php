<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Pagination\LengthAwarePaginator;
use Google\Cloud\Firestore\FieldValue;

class LandmarkController extends Controller
{
    protected $firestore;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firestore = $firebaseService->firestore();
    }

    
    public function map(Request $request)
    {
        $landmarksRef = $this->firestore->collection('landmarks');
        $documents = $landmarksRef->documents();

        $landmarks = [];
        foreach ($documents as $doc) {
            $data = $doc->data();
            $lat = $data['latitude'] ?? $data['lati'] ?? null;
            $lng = $data['longitude'] ?? $data['longti'] ?? null;

            if (is_numeric($lat) && is_numeric($lng)) {
                $data['latitude']  = (float) $lat;
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
        $snapshot = $this->firestore->collection('landmarks')->documents();

        
        $items = collect(iterator_to_array($snapshot));

        if ($request->filled('category')) {
            $items = $items->filter(function ($doc) use ($request) {
                $data = $doc->data();
                return isset($data['category']) && $data['category'] === $request->category;
            });
        }

        $perPage = 3;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $paginated = new LengthAwarePaginator(
            $items->forPage($currentPage, $perPage)->values(),
            $items->count(),
            $perPage,
            $currentPage,
            ['path' => url()->current(), 'query' => $request->query()]
        );

        return view('curators.landmarks.index', [
            'landmarks' => $paginated,
            'selectedCategory' => $request->category,
        ]);
    }

    
    public function create()
    {
        return view('curators.landmarks.create');
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string',
            'category'    => 'required|string',
            'description' => 'nullable|string',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
            'video_url'   => 'nullable|url',
            'image'       => 'nullable|image|max:512',
        ]);

        $imageBase64 = null;
        $imageMime = null;
        if ($request->hasFile('image')) {
            [$imageBase64, $imageMime] = $this->encodeImageToBase64($request->file('image')->getRealPath(), $request->file('image')->getMimeType());
        }

        
        $ref = $this->firestore->collection('landmarks')->add([
            'name'        => $request->name,
            'category'    => $request->category,
            'description' => $request->description,
            'latitude'    => is_numeric($request->latitude)  ? (float) $request->latitude  : null,
            'longitude'   => is_numeric($request->longitude) ? (float) $request->longitude : null,
            'video_url'   => $request->video_url,
            'image_base64'=> $imageBase64,
            'image_mime'  => $imageMime,
            'created_at'  => now(),
        ]);

        $landmarkId = $ref->id();

        if (!empty($imageBase64)) {
            $this->persistLandmarkImageFile($landmarkId, $imageBase64, $imageMime);
        }

        
        $code = 'LM-' . substr($landmarkId, 0, 6);

        
        $exists = $this->firestore->collection('qr_codes')->where('code', '==', $code)->limit(1)->documents();
        foreach ($exists as $ex) {
            if ($ex->exists()) { 
                $code = 'LM-' . substr(sha1($landmarkId . microtime(true)), 0, 6);
                break;
            }
        }

        $this->firestore->collection('qr_codes')->add([
            'code'        => $code,
            'landmark_id' => $landmarkId,
            'is_auto'     => true,
            'created_at'  => FieldValue::serverTimestamp(),
        ]);

        
        $this->generateQrImage($code, 'png');

        
        $this->firestore->collection('logs')->add([
            'email'     => Session::get('email'),
            'action'    => 'Added a Landmark',
            'timestamp' => now()->toISOString(),
        ]);

        return redirect()->route('landmarks.index')->with('success', 'Landmark added!');
    }

    
    public function edit($id)
    {
        $doc = $this->firestore->collection('landmarks')->document($id)->snapshot();
        if (!$doc->exists()) abort(404);
        return view('curators.landmarks.edit', ['id' => $id, 'landmark' => $doc->data()]);
    }

    
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'        => 'required|string',
            'category'    => 'required|string',
            'description' => 'nullable|string',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
            'video_url'   => 'nullable|url',
            'image'       => 'nullable|image|max:512',
        ]);

        $docRef = $this->firestore->collection('landmarks')->document($id);
        $doc = $docRef->snapshot();
        if (!$doc->exists()) abort(404);

        $data = $doc->data();

        if ($request->hasFile('image')) {
            [$imageBase64, $imageMime] = $this->encodeImageToBase64($request->file('image')->getRealPath(), $request->file('image')->getMimeType());

            $data['image_base64'] = $imageBase64;
            $data['image_mime'] = $imageMime;

            if (!empty($imageBase64)) {
                $this->persistLandmarkImageFile((string) $id, $imageBase64, $imageMime);
            }
        }

        $lat = $request->latitude ?? $data['latitude'] ?? null;
        $lng = $request->longitude ?? $data['longitude'] ?? null;

        $docRef->set([
            'name'        => $request->name,
            'category'    => $request->category,
            'description' => $request->description,
            'latitude'    => is_numeric($lat) ? (float) $lat : null,
            'longitude'   => is_numeric($lng) ? (float) $lng : null,
            'video_url'   => $request->video_url,
            'image_base64'=> $data['image_base64'] ?? null,
            'image_mime'  => $data['image_mime'] ?? null,
            'updated_at'  => now(),
        ], ['merge' => true]);

        $this->firestore->collection('logs')->add([
            'email'     => Session::get('email'),
            'action'    => 'Updated a Landmark',
            'timestamp' => now()->toISOString(),
        ]);

        return redirect()->route('landmarks.index')->with('success', 'Landmark updated.');
    }

    
    public function destroy($id)
    {
        $doc = $this->firestore->collection('landmarks')->document($id)->snapshot();
        if ($doc->exists()) {
            $this->firestore->collection('landmarks')->document($id)->delete();
            $this->deleteLandmarkImageFiles((string) $id);

            $this->firestore->collection('logs')->add([
                'email'     => Session::get('email'),
                'action'    => 'Deleted a Landmark',
                'timestamp' => now()->toISOString(),
            ]);
        }

        return redirect()->route('landmarks.index')->with('success', 'Landmark deleted.');
    }

    
    private function generateQrImage(string $code, string $format = 'png'): bool
    {
        
        $dir = 'qrcodes';
        $ext = in_array($format, ['png', 'svg']) ? $format : 'png';
        $path = "{$dir}/{$code}.{$ext}";

        try {
            if (!Storage::disk('public')->exists($dir)) {
                Storage::disk('public')->makeDirectory($dir);
            }

            $url = route('qr.resolve', ['code' => $code]);

            
            if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
                $qr = \SimpleSoftwareIO\QrCode\Facades\QrCode::format($ext)
                    ->size(600)->margin(1)->generate($url);

                Storage::disk('public')->put($path, $qr);
                return true;
            }

            
            if ($ext === 'svg') {
                $safe = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
                $svg = <<<SVG
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
                Storage::disk('public')->put($path, $svg);
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function encodeImageToBase64(string $filePath, ?string $mimeType = null): array
    {
        $raw = file_get_contents($filePath);
        $base64 = $raw !== false ? base64_encode($raw) : null;
        $mime = $mimeType ?: 'image/jpeg';

        return [$base64, $mime];
    }

    private function persistLandmarkImageFile(string $landmarkId, string $base64, ?string $mimeType = null): bool
    {
        if (str_contains($base64, ',')) {
            $parts = explode(',', $base64, 2);
            $base64 = $parts[1] ?? '';
        }

        $binary = base64_decode($base64, true);
        if ($binary === false) {
            return false;
        }

        $this->deleteLandmarkImageFiles($landmarkId);

        $ext = $this->extensionFromMime($mimeType ?: 'image/jpeg');
        Storage::disk('public')->put('landmarks/' . $landmarkId . '.' . $ext, $binary);

        return true;
    }

    private function deleteLandmarkImageFiles(string $landmarkId): void
    {
        $disk = Storage::disk('public');
        foreach (['jpg', 'jpeg', 'png', 'webp', 'gif'] as $ext) {
            $path = 'landmarks/' . $landmarkId . '.' . $ext;
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }

    private function extensionFromMime(string $mime): string
    {
        return match (strtolower(trim($mime))) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/jpeg', 'image/jpg' => 'jpg',
            default => 'jpg',
        };
    }
}
