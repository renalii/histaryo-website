<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Pagination\LengthAwarePaginator;

class LandmarkController extends Controller
{
    protected $firestore;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firestore = $firebaseService->firestore();
    }

    /**
     * Show landmarks on a map
     */
    public function map(Request $request, $id = null)
    {
        $landmarksRef = $this->firestore->collection('landmarks');
        $documents = $landmarksRef->documents();

        $landmarks = [];
        foreach ($documents as $doc) {
            $data = $doc->data();

            // Normalize coords: prefer latitude/longitude, fallback to old lati/longti
            $lat = $data['latitude'] ?? $data['lati'] ?? null;
            $lng = $data['longitude'] ?? $data['longti'] ?? null;

            if (is_numeric($lat) && is_numeric($lng)) {
                $data['latitude']  = (float) $lat;
                $data['longitude'] = (float) $lng;
                unset($data['lati'], $data['longti']); // cleanup if still exists
                $landmarks[] = array_merge($data, ['id' => $doc->id()]);
            }
        }

        $mapboxToken = config('services.mapbox.token');
        
        return view('curators.landmarks.map', compact('landmarks', 'mapboxToken'));
    }

    /**
     * Paginated list of landmarks
     */
    public function index(Request $request)
{
    $snapshot = $this->firestore->collection('landmarks')->documents();
    $items = collect($snapshot->rows());

    // ✅ Filter by category if selected
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
        [
            'path'  => url()->current(),
            'query' => $request->query(),
        ]
    );

    // ✅ Pass selected category for persistence
    return view('curators.landmarks.index', [
        'landmarks' => $paginated,
        'selectedCategory' => $request->category,
    ]);
}

    public function create()
    {
        return view('curators.landmarks.create');
    }

    /**
     * Store a new landmark
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'category' => 'required|string',
            'description' => 'nullable',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'video_url' => 'nullable|url',
            'image' => 'nullable|image|max:2048',
        ]);

        $lat = $request->latitude;
        $lng = $request->longitude;

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('landmarks', 'public');
        }

        $this->firestore->collection('landmarks')->add([
            'name' => $request->name,
            'category' => $request->category,
            'description' => $request->description,
            'latitude' => is_numeric($lat) ? (float) $lat : null,
            'longitude' => is_numeric($lng) ? (float) $lng : null,
            'video_url' => $request->video_url,
            'image_path' => $imagePath,
            'created_at' => now(),
        ]);

        $this->firestore->collection('logs')->add([
            'email' => Session::get('email'),
            'action' => 'Added a Landmark',
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

    /**
     * Update landmark
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'category' => 'required|string',
            'description' => 'nullable',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'video_url' => 'nullable|url',
            'image' => 'nullable|image|max:2048',
        ]);

        $docRef = $this->firestore->collection('landmarks')->document($id);
        $doc = $docRef->snapshot();
        if (!$doc->exists()) abort(404);

        $data = $doc->data();

        if ($request->hasFile('image')) {
            if (!empty($data['image_path'])) {
                Storage::disk('public')->delete($data['image_path']);
            }
            $data['image_path'] = $request->file('image')->store('landmarks', 'public');
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
            'image_path' => $data['image_path'] ?? null,
        ], ['merge' => true]);

        $this->firestore->collection('logs')->add([
            'email' => Session::get('email'),
            'action' => 'Updated a Landmark',
            'timestamp' => now()->toISOString(),
        ]);

        return redirect()->route('landmarks.index')->with('success', 'Updated successfully');
    }

    /**
     * Delete landmark
     */
    public function destroy($id)
    {
        $doc = $this->firestore->collection('landmarks')->document($id)->snapshot();
        if ($doc->exists()) {
            $data = $doc->data();
            if (!empty($data['image_path'])) {
                Storage::disk('public')->delete($data['image_path']);
            }
            $this->firestore->collection('landmarks')->document($id)->delete();

            $this->firestore->collection('logs')->add([
                'email' => Session::get('email'),
                'action' => 'Deleted a Landmark',
                'timestamp' => now()->toISOString(),
            ]);
        }

        return redirect()->route('landmarks.index')->with('success', 'Deleted successfully');
    }

    public function show($id)
    {
        $doc = $this->firestore->collection('landmarks')->document($id)->snapshot();
        if (!$doc->exists()) abort(404);
        return view('curators.landmarks.show', [
            'landmark' => $doc->data(),
            'id' => $doc->id(),
        ]);
    }
}
