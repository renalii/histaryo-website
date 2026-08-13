<?php

namespace App\Http\Controllers\SiteManager;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryImageService;
use App\Services\FirebaseService;
use App\Services\QrCodeImageStorage;
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
            if (! empty($landmark['image_path'] ?? null)) {
                $imageSrc = (string) $landmark['image_path'];
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
        $payload = [
            'name' => (string) $request->name,
            'landmarkcode' => $landmarkCode,
            'category' => (string) $request->category,
            'description' => $request->filled('description') ? (string) $request->description : '',
            'location' => $request->filled('location') ? (string) $request->location : '',
            'image_path' => '',
            'image_public_id' => '',
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
                'image_path' => QrCodeImageStorage::pathFor($landmarkId, $landmarkCode),
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

    public function update(Request $request, string $id)
    {
        $managerUid = (string) Session::get('uid', '');
        if ($managerUid === '') {
            abort(403);
        }

        $validator = Validator::make(
            $request->all(),
            array_merge([
                'name' => 'required|string',
                'category' => 'required|string',
                'description' => 'nullable|string',
                'location' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'image' => 'nullable|image|max:512',
                'evidence_files' => ['nullable', 'array', 'max:5'],
                'evidence_files.*' => [
                    'file',
                    'mimes:pdf,jpg,jpeg,png,webp,doc,docx',
                    'max:5120',
                ],
            ]),
            array_merge([
                'image.max' => 'The landmark photo must be 512 KB or smaller.',
            ], LandmarkEvidence::validationMessages())
        );

        if ($validator->fails()) {
            return redirect()->route('sitemanager.landmarks.show', $id)
                ->withErrors($validator)
                ->withInput();
        }

        $landmarkRef = $this->firebase->firestore()->collection('landmarks')->document($id);
        $snapshot = $landmarkRef->snapshot();
        if (! $snapshot->exists()) {
            return redirect()->route('sitemanager.landmarks')
                ->with('status_err', 'Landmark not found.');
        }

        $data = $snapshot->data();
        $ownerUid = trim((string) ($data['manager_uid'] ?? $data['managerUid'] ?? ''));
        if ($ownerUid === '' || $ownerUid !== $managerUid) {
            abort(403);
        }

        $payload = [
            'name' => (string) $request->name,
            'category' => (string) $request->category,
            'description' => $request->filled('description') ? (string) $request->description : '',
            'location' => $request->filled('location') ? (string) $request->location : '',
            'updated_at' => now()->toDateTimeString(),
        ];
        if (is_numeric($request->latitude)) {
            $payload['latitude'] = (float) $request->latitude;
        }
        if (is_numeric($request->longitude)) {
            $payload['longitude'] = (float) $request->longitude;
        }

        try {
            $landmarkRef->set($payload, ['merge' => true]);

            $evidenceFiles = $request->file('evidence_files', []);
            if (! is_array($evidenceFiles)) {
                $evidenceFiles = [];
            }
            if ($evidenceFiles !== []) {
                $landmarkRef->set([
                    'evidence_documents' => LandmarkEvidence::storeUploadedFiles($id, $evidenceFiles),
                ], ['merge' => true]);
            }

            if ($request->hasFile('image')) {
                $landmarkRef->set($this->cloudinary->uploadLandmark($request->file('image'), $id), ['merge' => true]);
            }

            $landmarkCode = $this->landmarkCode($data);
            if ($landmarkCode !== '') {
                $this->firebase->firestore()->collection('qrcodes')->document($landmarkCode)->set([
                    'landmarkName' => (string) $request->name,
                    'updatedAt' => FieldValue::serverTimestamp(),
                ], ['merge' => true]);
            }

            $this->siteManagerReadModel->forget($managerUid);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('sitemanager.landmarks.show', $id)
                ->with('status_err', 'Could not update landmark: '.$e->getMessage())
                ->withInput();
        }

        return redirect()->route('sitemanager.landmarks.show', $id)
            ->with('status', 'Landmark updated successfully.');
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
            return redirect()->route('sitemanager.landmarks')
                ->with('status_err', 'Landmark not found.');
        }

        $data = $snapshot->data();
        $ownerUid = trim((string) ($data['manager_uid'] ?? $data['managerUid'] ?? ''));
        if ($ownerUid === '' || $ownerUid !== $managerUid) {
            abort(403);
        }

        $landmarkCode = $this->landmarkCode($data);

        try {
            $this->deleteStoredQrCodeImage($id, $landmarkCode);
            $this->cloudinary->deleteLandmark((string) ($data['image_public_id'] ?? ''));
            $landmarkRef->delete();
            $this->siteManagerReadModel->forget($managerUid);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('sitemanager.landmarks')
                ->with('status_err', 'Could not delete landmark: '.$e->getMessage());
        }

        return redirect()->route('sitemanager.landmarks')
            ->with('status', 'Landmark deleted successfully.');
    }

    private function landmarkCode(array $landmarkData): string
    {
        foreach (['code', 'landmarkcode', 'landmark_code', 'landmarkCode', 'qr_code', 'qrCode'] as $field) {
            $code = trim((string) ($landmarkData[$field] ?? ''));
            if ($code !== '') {
                return $code;
            }
        }

        return '';
    }

    private function deleteStoredQrCodeImage(string $landmarkId, string $landmarkCode): void
    {
        try {
            if ($landmarkCode !== '') {
                $qrDoc = $this->firebase->firestore()->collection('qrcodes')->document($landmarkCode)->snapshot();
                if ($qrDoc->exists()) {
                    QrCodeImageStorage::deletePath((string) ($qrDoc->data()['image_path'] ?? ''));
                }
            }

            $manualQrDocs = $this->firebase->firestore()->collection('qr_codes')
                ->where('landmark_id', '==', $landmarkId)
                ->documents();
            foreach ($manualQrDocs as $qrDoc) {
                if ($qrDoc->exists()) {
                    QrCodeImageStorage::deletePath((string) ($qrDoc->data()['image_path'] ?? ''));
                    QrCodeImageStorage::deleteFor($landmarkId, (string) ($qrDoc->data()['code'] ?? $landmarkCode));
                }
            }

            QrCodeImageStorage::deleteFor($landmarkId, $landmarkCode);
            if ($landmarkCode !== '') {
                QrCodeImageStorage::deleteFor($landmarkCode, $landmarkId);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
