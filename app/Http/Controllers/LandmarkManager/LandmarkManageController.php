<?php

namespace App\Http\Controllers\LandmarkManager;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use App\Services\LandmarkImageStorage;
use App\Support\LandmarkEvidence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LandmarkManageController extends Controller
{
    public function __construct(
        protected FirebaseService $firebase,
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
                'video_url' => 'nullable|url',
                'image' => 'nullable|image|max:512',
            ], LandmarkEvidence::validationRules()),
            array_merge([
                'image.max' => 'The landmark photo must be 512 KB or smaller. Compress or resize the image, then choose it again—the file box clears after Save when validation fails.',
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

        $imageBase64 = null;
        $imageMime = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $raw = file_get_contents($file->getRealPath());
            $imageBase64 = $raw !== false ? base64_encode($raw) : null;
            $imageMime = $file->getMimeType();
        }

        $managerUid = (string) Session::get('uid');
        $submittedAt = now()->toDateTimeString();

        $payload = [
            'name' => (string) $request->name,
            'landmarkcode' => $landmarkCode,
            'category' => (string) $request->category,
            'description' => $request->filled('description') ? (string) $request->description : '',
            'video_url' => $request->filled('video_url') ? (string) $request->video_url : '',
            'image_base64' => $imageBase64 ?? '',
            'image_mime' => $imageMime ?? '',
            'manager_uid' => $managerUid,
            'activation_status' => 'pending',
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

            $evidenceDocuments = LandmarkEvidence::storeUploadedFiles($landmarkId, $evidenceFiles);
            if ($evidenceDocuments === []) {
                $ref->delete();

                return redirect()->route('sitemanager.landmarks')
                    ->withFragment('create-landmark')
                    ->withInput()
                    ->withErrors(['evidence_files' => 'Upload at least one valid evidence or supporting document.']);
            }

            $ref->set(['evidence_documents' => $evidenceDocuments], ['merge' => true]);

            if (! empty($imageBase64)) {
                LandmarkImageStorage::persistFromBase64($landmarkId, $imageBase64, $imageMime);
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
            ->with('status', 'Landmark submitted for administrator approval (code '.$landmarkCode.').');
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
