<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use App\Support\CuratorAssignedLandmark;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class LandmarkController extends Controller
{
    protected $firestore;

    public function __construct(FirebaseService $firebaseService)
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
        $docs = [];
        $landmarkManagerAttribution = null;

        foreach (CuratorAssignedLandmark::browseableIds() as $lid) {
            $snapshot = $this->firestore->collection('landmarks')->document($lid)->snapshot();
            if (! $snapshot->exists()) {
                continue;
            }
            $docs[] = $snapshot;
            if ($landmarkManagerAttribution === null && $assignedId !== null && $lid === $assignedId) {
                $landmarkManagerAttribution = $this->resolveLandmarkManagerAttributionLabel($snapshot->data());
            }
        }
        if ($landmarkManagerAttribution === null && $assignedId !== null) {
            $prim = $this->firestore->collection('landmarks')->document($assignedId)->snapshot();
            if ($prim->exists()) {
                $landmarkManagerAttribution = $this->resolveLandmarkManagerAttributionLabel($prim->data());
            }
        }

        $items = collect($docs);

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
            'landmarkManagerAttribution' => $landmarkManagerAttribution,
        ]);
    }

    public function edit($id)
    {
        CuratorAssignedLandmark::assertMatches((string) $id);

        $doc = $this->firestore->collection('landmarks')->document($id)->snapshot();
        if (! $doc->exists()) {
            abort(404);
        }

        return view('curators.landmarks.edit', ['id' => $id, 'landmark' => $doc->data()]);
    }

    public function update(Request $request, $id)
    {
        CuratorAssignedLandmark::assertMatches((string) $id);

        $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'video_url' => 'nullable|url',
            'image' => 'nullable|image|max:512',
        ]);

        $docRef = $this->firestore->collection('landmarks')->document($id);
        $doc = $docRef->snapshot();
        if (! $doc->exists()) {
            abort(404);
        }

        $data = $doc->data();

        if ($request->hasFile('image')) {
            [$imageBase64, $imageMime] = $this->encodeImageToBase64($request->file('image')->getRealPath(), $request->file('image')->getMimeType());

            $data['image_base64'] = $imageBase64;
            $data['image_mime'] = $imageMime;

            if (! empty($imageBase64)) {
                $this->persistLandmarkImageFile((string) $id, $imageBase64, $imageMime);
            }
        }

        $lat = $request->latitude ?? $data['latitude'] ?? null;
        $lng = $request->longitude ?? $data['longitude'] ?? null;

        $docRef->set([
            'name' => $request->name,
            'category' => $request->category,
            'description' => $request->description,
            'latitude' => is_numeric($lat) ? (float) $lat : null,
            'longitude' => is_numeric($lng) ? (float) $lng : null,
            'video_url' => $request->video_url,
            'image_base64' => $data['image_base64'] ?? null,
            'image_mime' => $data['image_mime'] ?? null,
            'updated_at' => now(),
        ], ['merge' => true]);

        $this->firestore->collection('logs')->add([
            'email' => Session::get('email'),
            'action' => 'Updated a Landmark',
            'timestamp' => now()->toISOString(),
        ]);

        return redirect()->route('landmarks.index')->with('success', 'Landmark updated.');
    }

    public function destroy(string $id)
    {
        CuratorAssignedLandmark::assertMatches((string) $id);

        $docRef = $this->firestore->collection('landmarks')->document($id);
        $doc = $docRef->snapshot();
        if (! $doc->exists()) {
            abort(404);
        }

        foreach ($this->firestore->collection('qr_codes')->where('landmark_id', '==', $id)->documents() as $qrDoc) {
            if (! $qrDoc->exists()) {
                continue;
            }
            $code = (string) ($qrDoc->data()['code'] ?? '');
            $qrDoc->reference()->delete();
            if ($code !== '') {
                foreach (['png', 'svg'] as $ext) {
                    try {
                        Storage::disk('public')->delete("qrcodes/{$code}.{$ext}");
                    } catch (\Throwable $e) {
                    }
                }
            }
        }

        foreach ($this->firestore->collection('question_bank')->where('landmark_id', '=', $id)->documents() as $triviaDoc) {
            if ($triviaDoc->exists()) {
                $triviaDoc->reference()->delete();
            }
        }

        $this->deleteLandmarkImageFiles($id);
        $docRef->delete();

        $this->firestore->collection('logs')->add([
            'email' => Session::get('email'),
            'action' => 'Deleted a Landmark',
            'timestamp' => now()->toISOString(),
        ]);

        return redirect()->route('landmarks.index')->with('success', 'Landmark deleted.');
    }

    private function resolveLandmarkManagerAttributionLabel(array $landmarkData): ?string
    {
        $managerUid = isset($landmarkData['manager_uid']) ? trim((string) $landmarkData['manager_uid']) : '';
        if ($managerUid === '') {
            return null;
        }

        $prof = $this->firestore->collection('users')->document($managerUid)->snapshot();
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
        Storage::disk('public')->put('landmarks/'.$landmarkId.'.'.$ext, $binary);

        return true;
    }

    private function deleteLandmarkImageFiles(string $landmarkId): void
    {
        $disk = Storage::disk('public');
        foreach (['jpg', 'jpeg', 'png', 'webp', 'gif'] as $ext) {
            $path = 'landmarks/'.$landmarkId.'.'.$ext;
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
