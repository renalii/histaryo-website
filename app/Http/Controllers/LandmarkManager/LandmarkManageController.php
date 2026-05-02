<?php

namespace App\Http\Controllers\LandmarkManager;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use App\Services\LandmarkJoinQrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LandmarkManageController extends Controller
{
    public function __construct(
        protected FirebaseService $firebase,
        protected LandmarkJoinQrService $joinQrService
    ) {}

    public function create()
    {
        return view('landmarkmanager.landmarks-create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'landmarkcode' => ['required', 'string', 'max:48', 'regex:/^[A-Za-z0-9_-]+$/'],
            'category' => 'required|string',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'video_url' => 'nullable|url',
            'image' => 'nullable|image|max:512',
        ]);

        $landmarkCode = strtoupper(trim((string) $request->input('landmarkcode', '')));

        $dup = $this->firebase->firestore()->collection('landmarks')
            ->where('landmarkcode', '==', $landmarkCode)
            ->limit(1)
            ->documents();
        foreach ($dup as $doc) {
            if ($doc->exists()) {
                return redirect()->back()
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

        $ref = $this->firebase->firestore()->collection('landmarks')->add([
            'name' => $request->name,
            'landmarkcode' => $landmarkCode,
            'category' => $request->category,
            'description' => $request->description,
            'latitude' => is_numeric($request->latitude) ? (float) $request->latitude : null,
            'longitude' => is_numeric($request->longitude) ? (float) $request->longitude : null,
            'video_url' => $request->video_url,
            'image_base64' => $imageBase64,
            'image_mime' => $imageMime,
            'manager_uid' => $managerUid,
            'activation_status' => 'active',
            'created_at' => now(),
        ]);

        $landmarkId = $ref->id();

        $code = $this->joinQrService->ensureJoinQrForLandmark($landmarkId);

        $this->firebase->firestore()->collection('logs')->add([
            'email' => Session::get('email'),
            'action' => 'Landmark Manager created landmark',
            'landmark_id' => $landmarkId,
            'timestamp' => now()->toISOString(),
        ]);

        return redirect()->route('landmarkmanager.landmarks')
            ->with('status', 'Landmark created. Site code '.$landmarkCode.' · Curator join code: '.$code);
    }
}
