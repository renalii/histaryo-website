<?php

namespace App\Http\Controllers\SiteManager;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryImageService;
use App\Services\FirebaseService;
use App\Support\LandmarkEvidence;
use App\Support\LandmarkVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SiteManagerController extends Controller
{
    public function __construct(
        protected FirebaseService $firebase,
        protected CloudinaryImageService $cloudinary,
    ) {}

    public function create()
    {
        return redirect()->route('sitemanager.landmarks')->withFragment('create-landmark');
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            array_merge([
                'name' => 'required|string',
                'landmarkcode' => ['nullable', 'string', 'max:48', 'regex:/^[A-Za-z0-9_-]*$/'],
                'category' => 'required|string',
                'description' => 'nullable|string',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'video' => 'nullable|file|mimes:mp4,mov,avi,webm,mkv|max:51200',
                'image' => 'nullable|image|max:512',
            ], LandmarkEvidence::validationRules()),
            array_merge([
                'image.max' => 'The landmark photo must be 512 KB or smaller. Compress or resize the image, then choose it again—the file box clears after Save when validation fails.',
                'video.mimes' => 'The video must be an MP4, MOV, AVI, WebM, or MKV file.',
                'video.max' => 'The video must be 50 MB or smaller.',
            ], LandmarkEvidence::validationMessages())
        );

        if ($validator->fails()) {
            return redirect()->route('sitemanager.landmarks')
                ->withFragment('create-landmark')
                ->withErrors($validator)
                ->withInput();
        }

        $evidenceFiles = $request->file('evidence_files', []);
        if (! is_array($evidenceFiles)) {
            $evidenceFiles = [];
        }
        if ($evidenceFiles === []) {
            return redirect()->route('sitemanager.landmarks')
                ->withFragment('create-landmark')
                ->withInput()
                ->withErrors(['evidence_files' => 'Upload at least one valid evidence or supporting document.']);
        }

        $landmarkCode = strtoupper(trim((string) $request->input('landmarkcode', '')));
        if ($landmarkCode === '') {
            $landmarkCode = $this->generateUniqueLandmarkCode();
        }

        $dup = $this->firebase->firestore()->collection('landmarks')
            ->where('landmarkcode', '==', $landmarkCode)
            ->limit(1)
            ->documents();
        foreach ($dup as $doc) {
            if ($doc->exists()) {
                return redirect()->route('sitemanager.landmarks')
                    ->withFragment('create-landmark')
                    ->withInput()
                    ->withErrors(['landmarkcode' => 'This landmark code is already in use. Choose a different code.']);
            }
        }

        $managerUid = (string) Session::get('uid');
        $submittedAt = now()->toDateTimeString();

        $payload = [
            'name' => (string) $request->name,
            'landmarkcode' => $landmarkCode,
            'category' => (string) $request->category,
            'description' => $request->filled('description') ? (string) $request->description : '',
            'video_url' => '',
            'image_url' => '',
            'image_public_id' => '',
            'image_base64' => '',
            'image_mime' => '',
            'manager_uid' => $managerUid,
            'activation_status' => 'pending',
            'visibility' => LandmarkVisibility::HIDDEN,
            'submitted_at' => $submittedAt,
            'created_at' => $submittedAt,
        ];
        if (is_numeric($request->latitude)) {
            $payload['latitude'] = (float) $request->latitude;
        }
        if (is_numeric($request->longitude)) {
            $payload['longitude'] = (float) $request->longitude;
        }

        try {
            $ref = $this->firebase->firestore()->collection('landmarks')->add($payload);
            $landmarkId = $ref->id();

            if ($request->hasFile('video')) {
                $videoFile = $request->file('video');
                $videoPath = $videoFile->store('landmark-videos/'.$landmarkId, 'public');
                $ref->set([
                    'video_url' => asset(Storage::url($videoPath)),
                    'video_path' => $videoPath,
                    'video_mime' => $videoFile->getMimeType(),
                    'video_filename' => $videoFile->getClientOriginalName(),
                    'video_is_upload' => true,
                ], ['merge' => true]);
            }

            $evidenceDocuments = LandmarkEvidence::storeUploadedFiles($landmarkId, $evidenceFiles);
            if ($evidenceDocuments === []) {
                $ref->delete();

                return redirect()->route('sitemanager.landmarks')
                    ->withFragment('create-landmark')
                    ->withInput()
                    ->withErrors(['evidence_files' => 'Upload at least one valid evidence or supporting document.']);
            }

            $ref->set(['evidence_documents' => $evidenceDocuments], ['merge' => true]);

            if ($request->hasFile('image')) {
                $ref->set($this->cloudinary->uploadLandmark($request->file('image'), $landmarkId), ['merge' => true]);
            }

        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('sitemanager.landmarks')
                ->withFragment('create-landmark')
                ->withInput()
                ->withErrors(['evidence_files' => 'Could not save the landmark. '.$e->getMessage()]);
        }

        $this->firebase->firestore()->collection('logs')->add([
            'email' => (string) Session::get('email', ''),
            'action' => 'Site Manager submitted landmark for approval',
            'landmark_id' => $landmarkId,
            'timestamp' => now()->toIso8601String(),
        ]);

        return redirect()->route('sitemanager.landmarks')
            ->with('status', 'Landmark submitted for administrator approval.');
    }

    public function updateVisibility(Request $request, string $id)
    {
        $validated = $request->validate([
            'visibility' => ['required', 'string', 'in:published,archived,hidden'],
        ]);

        $managerUid = trim((string) Session::get('uid', ''));
        $ref = $this->firebase->firestore()->collection('landmarks')->document($id);
        $snapshot = $ref->snapshot();
        if (! $snapshot->exists()) {
            abort(404);
        }

        $data = $snapshot->data();
        $ownerUid = trim((string) ($data['manager_uid'] ?? $data['managerUid'] ?? ''));
        if ($managerUid === '' || $ownerUid === '' || $managerUid !== $ownerUid) {
            abort(403);
        }

        $visibility = LandmarkVisibility::normalize($validated['visibility']);
        $ref->set([
            'visibility' => $visibility,
            'visibility_updated_at' => now()->toISOString(),
            'visibility_updated_by_uid' => $managerUid,
            'updated_at' => now()->toDateTimeString(),
        ], ['merge' => true]);

        $this->firebase->firestore()->collection('logs')->add([
            'email' => (string) Session::get('email', ''),
            'action' => 'Site Manager changed landmark visibility to '.LandmarkVisibility::label($visibility),
            'landmark_id' => $id,
            'timestamp' => now()->toISOString(),
        ]);

        return redirect()->route('sitemanager.landmarks.show', $id)
            ->with('status', 'Landmark visibility updated to '.LandmarkVisibility::label($visibility).'.');
    }

    protected function generateUniqueLandmarkCode(): string
    {
        for ($i = 0; $i < 24; $i++) {
            $candidate = 'LM'.strtoupper(Str::random(6));
            $dup = $this->firebase->firestore()->collection('landmarks')
                ->where('landmarkcode', '==', $candidate)
                ->limit(1)
                ->documents();
            $taken = false;
            foreach ($dup as $doc) {
                if ($doc->exists()) {
                    $taken = true;
                    break;
                }
            }
            if (! $taken) {
                return $candidate;
            }
        }

        return 'LM'.strtoupper(Str::random(10));
    }
}
