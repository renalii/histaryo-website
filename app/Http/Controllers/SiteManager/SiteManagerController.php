<?php

namespace App\Http\Controllers\SiteManager;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryImageService;
use App\Services\FirebaseService;
use App\Services\SiteManagerReadModel;
use App\Support\LandmarkEvidence;
use App\Support\QrResolveUrl;
use Google\Cloud\Firestore\FieldValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SiteManagerController extends Controller
{
    public function __construct(
        protected FirebaseService $firebase,
        protected CloudinaryImageService $cloudinary,
        protected SiteManagerReadModel $siteManagerReadModel,
    ) {}

    public function create()
    {
        return redirect()->route('sitemanager.landmarks')->withFragment('create-landmark');
    }

    public function map(Request $request)
    {
        $managerUid = trim((string) $request->session()->get('uid', ''));
        $landmarks = [];

        foreach ($this->siteManagerReadModel->landmarks($managerUid) as $landmark) {
            $lat = $landmark['latitude'] ?? $landmark['lati'] ?? null;
            $lng = $landmark['longitude'] ?? $landmark['longti'] ?? null;

            if (! is_numeric($lat) || ! is_numeric($lng)) {
                continue;
            }

            $location = trim((string) ($landmark['location'] ?? ''));
            if ($location === '') {
                $location = number_format((float) $lat, 6).', '.number_format((float) $lng, 6);
            }

            $landmarkId = (string) ($landmark['id'] ?? '');
            $imageSrc = '';
            if (! empty($landmark['image_url'] ?? null)) {
                $imageSrc = (string) $landmark['image_url'];
            } elseif (! empty($landmark['image_base64'] ?? null)) {
                $imageBase64 = (string) $landmark['image_base64'];
                $imageSrc = str_starts_with($imageBase64, 'data:')
                    ? $imageBase64
                    : 'data:'.($landmark['image_mime'] ?? 'image/jpeg').';base64,'.$imageBase64;
            }

            $landmarks[] = [
                'id' => $landmarkId,
                'name' => (string) ($landmark['name'] ?? 'Untitled'),
                'status' => (string) ($landmark['activation_status'] ?? 'active'),
                'location' => $location,
                'description' => (string) ($landmark['description'] ?? ''),
                'imageSrc' => $imageSrc,
                'detailsUrl' => $landmarkId !== '' ? route('sitemanager.landmarks.show', $landmarkId) : '',
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
            ];
        }

        return view('sitemanager.map', [
            'landmarks' => $landmarks,
            'mapboxToken' => config('services.mapbox.token'),
        ]);
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
                'location' => 'nullable|string|max:255',
                'tags' => 'nullable|string|max:500',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
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

        $managerUid = (string) Session::get('uid');
        $submittedAt = now()->toDateTimeString();
        $tags = collect(preg_split('/[,;\r\n]+/', (string) $request->input('tags', ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->unique(fn ($tag) => strtolower($tag))
            ->values()
            ->all();

        $payload = [
            'name' => (string) $request->name,
            'landmarkcode' => $landmarkCode,
            'category' => (string) $request->category,
            'description' => $request->filled('description') ? (string) $request->description : '',
            'location' => $request->filled('location') ? (string) $request->location : '',
            'tags' => $tags,
            'image_url' => '',
            'image_public_id' => '',
            'image_base64' => '',
            'image_mime' => '',
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
            $this->firebase->firestore()->collection('qrcodes')->document($landmarkCode)->set([
                'code' => $landmarkCode,
                'landmarkId' => $landmarkId,
                'landmarkCode' => $landmarkCode,
                'landmarkName' => (string) $request->name,
                'qrUrl' => QrResolveUrl::forCode($landmarkCode),
                'status' => 'active',
                'createdAt' => FieldValue::serverTimestamp(),
            ], ['merge' => true]);

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
            'role' => 'site_manager',
            'action' => 'Site Manager submitted landmark for approval: '.(string) $request->name,
            'landmark_id' => $landmarkId,
            'landmark_name' => (string) $request->name,
            'timestamp' => now()->toIso8601String(),
        ]);
        $this->siteManagerReadModel->forget($managerUid);

        return redirect()->route('sitemanager.landmarks')
            ->with('status', 'Landmark submitted for administrator approval.');
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

    public function destroy(Request $request, string $id)
    {
        $managerUid = (string) Session::get('uid', '');
        if ($managerUid === '') {
            abort(403);
        }

        $landmarkRef = $this->firebase->firestore()->collection('landmarks')->document($id);
        $snapshot = $landmarkRef->snapshot();
        if (! $snapshot->exists()) {
            return redirect()->route('sitemanager.landmarks', ['view' => 'list'])
                ->with('status_err', 'Landmark not found.');
        }

        $data = $snapshot->data();
        $ownerUid = trim((string) ($data['manager_uid'] ?? $data['managerUid'] ?? ''));
        if ($ownerUid === '' || $ownerUid !== $managerUid) {
            abort(403);
        }

        $landmarkName = trim((string) ($data['name'] ?? ''));

        try {
            $landmarkRef->delete();
            $this->firebase->firestore()->collection('logs')->add([
                'email' => (string) Session::get('email', ''),
                'role' => 'site_manager',
                'action' => 'Site Manager deleted landmark: '.($landmarkName !== '' ? $landmarkName : $id),
                'landmark_id' => $id,
                'landmark_name' => $landmarkName,
                'timestamp' => now()->toISOString(),
            ]);
            $this->siteManagerReadModel->forget($managerUid);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('sitemanager.landmarks', ['view' => 'list'])
                ->with('status_err', 'Could not delete landmark: '.$e->getMessage());
        }

        return redirect()->route('sitemanager.landmarks', ['view' => 'list'])
            ->with('status', 'Landmark deleted successfully.');
    }
}
