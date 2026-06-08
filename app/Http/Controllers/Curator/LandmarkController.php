<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryImageService;
use App\Services\FirebaseService;
use App\Services\LandmarkImageStorage;
use App\Support\CuratorAssignedLandmark;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class LandmarkController extends Controller
{
    protected $firestore;

    public function __construct(
        protected FirebaseService $firebaseService,
        protected CloudinaryImageService $cloudinary,
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

        return view('curators.landmarks.index', [
            'landmark' => $snapshot,
            'siteManagerAttribution' => $this->resolveSiteManagerAttributionLabel($snapshot->data()),
            'mapboxToken' => config('services.mapbox.token'),
        ]);
    }

    public function edit(string $landmark)
    {
        CuratorAssignedLandmark::assertMatches($landmark);

        $snapshot = $this->firestore->collection('landmarks')->document($landmark)->snapshot();
        if (! $snapshot->exists()) {
            abort(404);
        }

        return view('curators.landmarks.edit', [
            'id' => $landmark,
            'landmark' => $snapshot->data(),
            'mapboxToken' => config('services.mapbox.token'),
        ]);
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
            'video' => 'nullable|file|mimes:mp4,mov,avi,webm,mkv|max:102400',
            'image' => 'nullable|image|max:512',
        ], [
            'video.mimes' => 'The video must be an MP4, MOV, AVI, WebM, or MKV file.',
            'video.max' => 'The video must be 100 MB or smaller.',
        ]);

        $docRef = $this->firestore->collection('landmarks')->document($id);
        $doc = $docRef->snapshot();
        if (! $doc->exists()) {
            abort(404);
        }

        $data = $doc->data();
        $previousImagePublicId = trim((string) ($data['image_public_id'] ?? ''));

        if ($request->hasFile('image')) {
            $data = array_merge($data, $this->cloudinary->uploadLandmark($request->file('image'), (string) $id));
        }

        $videoData = [
            'video_url' => $data['video_url'] ?? '',
            'video_path' => $data['video_path'] ?? null,
            'video_mime' => $data['video_mime'] ?? null,
            'video_filename' => $data['video_filename'] ?? null,
            'video_is_upload' => $data['video_is_upload'] ?? false,
        ];

        if ($request->hasFile('video')) {
            $videoFile = $request->file('video');

            if (! empty($data['video_path']) && is_string($data['video_path'])) {
                Storage::disk('public')->delete($data['video_path']);
            }

            $videoPath = $videoFile->store('landmark-videos/'.$id, 'public');
            $videoData = [
                'video_url' => asset(Storage::url($videoPath)),
                'video_path' => $videoPath,
                'video_mime' => $videoFile->getMimeType(),
                'video_filename' => $videoFile->getClientOriginalName(),
                'video_is_upload' => true,
            ];
        }

        $lat = $request->latitude ?? $data['latitude'] ?? null;
        $lng = $request->longitude ?? $data['longitude'] ?? null;

        $docRef->set(array_merge([
            'name' => $request->name,
            'category' => $request->category,
            'description' => $request->description,
            'latitude' => is_numeric($lat) ? (float) $lat : null,
            'longitude' => is_numeric($lng) ? (float) $lng : null,
            'image_url' => $data['image_url'] ?? '',
            'image_public_id' => $data['image_public_id'] ?? '',
            'image_base64' => $data['image_base64'] ?? '',
            'image_mime' => $data['image_mime'] ?? '',
            'updated_at' => now(),
        ], $videoData), ['merge' => true]);

        if ($request->hasFile('image')) {
            $newImagePublicId = trim((string) ($data['image_public_id'] ?? ''));
            if ($previousImagePublicId !== '' && $previousImagePublicId !== $newImagePublicId) {
                $this->cloudinary->deleteLandmark($previousImagePublicId);
            }
            LandmarkImageStorage::deleteForLandmark((string) $id);
        }

        $this->firestore->collection('logs')->add([
            'email' => Session::get('email'),
            'action' => 'Updated a Landmark',
            'timestamp' => now()->toISOString(),
        ]);

        return redirect()->route('landmarks.show', $id)->with('success', 'Landmark updated.');
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

        foreach ($this->firestore->collection('question_bank')->where('landmark_id', '=', $id)->documents() as $quizDoc) {
            if ($quizDoc->exists()) {
                $quizDoc->reference()->delete();
            }
        }

        $this->cloudinary->deleteLandmark((string) ($doc->data()['image_public_id'] ?? ''));
        LandmarkImageStorage::deleteForLandmark($id);
        $docRef->delete();

        $this->firestore->collection('logs')->add([
            'email' => Session::get('email'),
            'action' => 'Deleted a Landmark',
            'timestamp' => now()->toISOString(),
        ]);

        return redirect()->route('curators.dashboard')->with('success', 'Landmark deleted.');
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

}
