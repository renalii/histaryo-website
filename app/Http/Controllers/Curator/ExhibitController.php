<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use App\Services\ExhibitMediaStorage;
use App\Services\FirebaseService;
use App\Services\SiteManagerReadModel;
use App\Support\CuratorAssignedLandmark;
use App\Support\ArrayDocumentSnapshot;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ExhibitController extends Controller
{
    private ?array $availableLandmarksCache = null;

    public function __construct(
        private FirebaseService $firebase,
        private ExhibitMediaStorage $mediaStorage,
        private SiteManagerReadModel $siteManagerReadModel,
    ) {}

    public function index(Request $request, ?string $id = null)
    {
        $landmarks = $this->availableLandmarks();
        if ($landmarks === []) {
            abort(403);
        }

        $landmarkIds = array_column($landmarks, 'id');
        $landmarkById = array_column($landmarks, null, 'id');
        $landmark = $landmarks[0];
        $selectedLandmarkId = trim((string) $request->query('landmark', 'all'));
        if ($this->isCurator()) {
            $selectedLandmarkId = $landmark['id'];
        } elseif ($selectedLandmarkId !== 'all' && ! in_array($selectedLandmarkId, $landmarkIds, true)) {
            $selectedLandmarkId = 'all';
        }
        $searchValue = trim((string) $request->query('search', ''));
        $search = strtolower($searchValue);
        $category = trim((string) $request->query('category', 'all'));
        $status = strtolower(trim((string) $request->query('status', 'all')));
        if (! in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }
        $sort = strtolower(trim((string) $request->query('sort', '')));
        $sortDirection = strtolower(trim((string) $request->query('direction', 'asc')));
        $sortFields = [
            'name' => 'name',
            'category' => 'category',
            'landmark' => 'landmark_name',
        ];
        if (! isset($sortFields[$sort])) {
            $sort = null;
            $sortDirection = null;
        } elseif (! in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'asc';
        }

        $start = microtime(true);
        $exhibits = [];
        $managerUid = $this->managerUid();
        $categoryOptions = $this->activeCategoryNamesForManager($managerUid);
        $queryLandmarkIds = $selectedLandmarkId === 'all' ? $landmarkIds : [$selectedLandmarkId];
        foreach ($this->exhibitDocumentsForLandmarks($queryLandmarkIds, $sort !== null ? $sortFields[$sort] : null, $sortDirection) as $doc) {
            if (! $doc->exists()) {
                continue;
            }

            $data = $doc->data();
            $rowLandmarkId = trim((string) ($data['landmark_id'] ?? ''));
            if (! in_array($rowLandmarkId, $landmarkIds, true)) {
                continue;
            }
            if ($selectedLandmarkId !== 'all' && $rowLandmarkId !== $selectedLandmarkId) {
                continue;
            }

            $rowStatus = strtolower((string) ($data['status'] ?? 'active')) === 'inactive' ? 'inactive' : 'active';
            $rowCategory = trim((string) ($data['category'] ?? ''));
            $rowLandmarkName = trim((string) ($data['landmark_name'] ?? ($landmarkById[$rowLandmarkId]['name'] ?? 'Assigned landmark')));
            if ($rowCategory !== '' && ! in_array($rowCategory, $categoryOptions, true)) {
                $categoryOptions[] = $rowCategory;
            }

            if ($category !== 'all' && strcasecmp($rowCategory, $category) !== 0) {
                continue;
            }

            if ($status !== 'all' && $rowStatus !== $status) {
                continue;
            }

            if ($search !== '') {
                $haystack = strtolower(trim((string) ($data['name'] ?? '')).' '.$rowCategory.' '.$rowLandmarkName);
                if (! str_contains($haystack, $search)) {
                    continue;
                }
            }

            $exhibits[] = array_merge($data, [
                'id' => $doc->id(),
                'name' => (string) ($data['name'] ?? 'Untitled exhibit'),
                'category' => $rowCategory,
                'description' => (string) ($data['description'] ?? ''),
                'historical_info' => (string) ($data['historical_info'] ?? ''),
                'year_period' => (string) ($data['year_period'] ?? ''),
                'status' => $rowStatus,
                'images' => is_array($data['images'] ?? null) ? $data['images'] : [],
                'landmark_id' => $rowLandmarkId,
                'landmark_name' => $rowLandmarkName,
            ]);
        }

        usort($exhibits, function (array $left, array $right) use ($sort, $sortDirection): int {
            if ($sort !== null) {
                $leftValue = (string) ($left[$sort === 'landmark' ? 'landmark_name' : $sort] ?? '');
                $rightValue = (string) ($right[$sort === 'landmark' ? 'landmark_name' : $sort] ?? '');
                $comparison = strnatcasecmp($leftValue, $rightValue);
                if ($comparison !== 0) {
                    return $sortDirection === 'desc' ? -$comparison : $comparison;
                }
            }

            $leftDate = strtotime((string) ($left['created_at'] ?? '')) ?: 0;
            $rightDate = strtotime((string) ($right['created_at'] ?? '')) ?: 0;

            return $rightDate <=> $leftDate ?: strcasecmp((string) $left['name'], (string) $right['name']);
        });

        $perPage = 5;
        $page = max(1, (int) $request->query('page', 1));

        if ($id !== null) {
            $selectedIndex = array_search($id, array_column($exhibits, 'id'), true);
            if ($selectedIndex === false) {
                return redirect()->route($this->routeName('exhibits.index'))
                    ->with('error', 'Exhibit not found.');
            }
            $page = (int) floor($selectedIndex / $perPage) + 1;
        }

        $lastPage = max(1, (int) ceil(count($exhibits) / $perPage));
        $page = min($page, $lastPage);

        $paginator = new LengthAwarePaginator(
            array_slice($exhibits, ($page - 1) * $perPage, $perPage),
            count($exhibits),
            $perPage,
            $page,
            [
                'path' => route($this->routeName('exhibits.index')),
                'query' => $request->only(['search', 'category', 'landmark', 'status', 'sort', 'direction']),
            ]
        );

        Log::info('Timing curator page', [
            'route' => $this->routeName('exhibits.index'),
            'landmark_count' => count($queryLandmarkIds),
            'records' => count($exhibits),
            'duration_ms' => (int) round((microtime(true) - $start) * 1000),
        ]);

        return view('curators.exhibits.index', [
            'exhibits' => $paginator,
            'landmark' => $landmark,
            'landmarkOptions' => $landmarks,
            'selectedLandmarkId' => $selectedLandmarkId,
            'canSelectLandmark' => $this->isSiteManager(),
            'routePrefix' => $this->routePrefix(),
            'search' => $searchValue,
            'categoryFilter' => $category,
            'categoryOptions' => $this->uniqueCategories($categoryOptions),
            'statusFilter' => $status,
            'sort' => $sort,
            'sortDirection' => $sortDirection,
            'openViewId' => $id,
        ]);
    }

    public function store(Request $request)
    {
        $validator = $this->validator($request, true, $this->managerUid());
        if ($validator->fails()) {
            return redirect()->route($this->routeName('exhibits.index'))
                ->withErrors($validator)
                ->withInput($request->merge(['_form' => 'create'])->all());
        }
        $landmark = $this->writableLandmark($request);

        $exhibitId = Str::random(20);
        $images = $this->mediaStorage->storeImages($landmark['id'], $exhibitId, $this->uploadedFiles($request, 'images'));

        $payload = $this->payload($request, $landmark) + [
            'landmark_id' => $landmark['id'],
            'landmark_name' => $landmark['name'],
            'curator_uid' => (string) Session::get('uid', ''),
            'images' => $images,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];

        $this->firebase->firestore()->collection('exhibits')->document($exhibitId)->set($payload);
        $this->forgetExhibitDocumentsCache([$landmark['id']]);

        return redirect()->route($this->routeName('exhibits.index'))->with('success', 'Exhibit added successfully.');
    }

    public function update(Request $request, string $id)
    {
        $snapshot = $this->exhibitSnapshot($id);
        $validator = $this->validator($request, false, $this->managerUid());
        if ($validator->fails()) {
            return redirect()->route($this->routeName('exhibits.index'), ['edit' => $id])
                ->withErrors($validator)
                ->withInput($request->merge(['_form' => 'edit', '_edit_id' => $id])->all());
        }
        $landmark = $this->writableLandmark($request, trim((string) ($snapshot->data()['landmark_id'] ?? '')));

        $existing = $snapshot->data();
        $images = is_array($existing['images'] ?? null) ? $existing['images'] : [];
        $removeImagePaths = array_values(array_filter(array_map('strval', (array) $request->input('remove_images', []))));
        if ($removeImagePaths !== []) {
            $imagesToDelete = array_values(array_filter($images, fn ($image): bool => is_array($image) && in_array((string) ($image['path'] ?? ''), $removeImagePaths, true)));
            $this->mediaStorage->deleteMany($imagesToDelete);
            $images = array_values(array_filter($images, fn ($image): bool => is_array($image) && ! in_array((string) ($image['path'] ?? ''), $removeImagePaths, true)));
        }

        $newImages = $this->mediaStorage->storeImages($landmark['id'], $id, $this->uploadedFiles($request, 'images'));
        $images = array_merge($images, $newImages);

        $payload = $this->payload($request, $landmark) + [
            'landmark_id' => $landmark['id'],
            'images' => $images,
            'updated_at' => now()->toDateTimeString(),
        ];

        $this->firebase->firestore()->collection('exhibits')->document($id)->set($payload, ['merge' => true]);
        $this->forgetExhibitDocumentsCache([
            (string) ($snapshot->data()['landmark_id'] ?? ''),
            (string) ($landmark['id'] ?? ''),
        ]);

        return redirect()->route($this->routeName('exhibits.index'))->with('success', 'Exhibit updated successfully.');
    }

    public function destroy(string $id)
    {
        $snapshot = $this->exhibitSnapshot($id);
        $data = $snapshot->data();

        $this->mediaStorage->deleteMany(is_array($data['images'] ?? null) ? $data['images'] : []);

        $this->firebase->firestore()->collection('exhibits')->document($id)->delete();
        $this->forgetExhibitDocumentsCache([(string) ($data['landmark_id'] ?? '')]);

        return redirect()->route($this->routeName('exhibits.index'))->with('success', 'Exhibit deleted successfully.');
    }

    /** @return array{id:string,name:string,manager_uid:string} */
    private function assignedLandmark(): array
    {
        $landmarkId = CuratorAssignedLandmark::id();
        if ($landmarkId === null) {
            abort(403);
        }
        CuratorAssignedLandmark::assertMatches($landmarkId);

        return Cache::remember('curator:assigned-landmark:'.$landmarkId, now()->addMinutes(10), function () use ($landmarkId): array {
            $snapshot = $this->firebase->firestore()->collection('landmarks')->document($landmarkId)->snapshot();
            $data = $snapshot->exists() ? $snapshot->data() : [];

            $managerUid = trim((string) ($data['manager_uid'] ?? $data['managerUid'] ?? ''));
            if ($managerUid === '') {
                $managerUid = $this->curatorManagerUidFromProfile();
            }

            return [
                'id' => $landmarkId,
                'name' => trim((string) ($data['name'] ?? '')) ?: 'Assigned landmark',
                'manager_uid' => $managerUid,
            ];
        });
    }

    /** @return list<array{id:string,name:string,manager_uid:string}> */
    private function availableLandmarks(): array
    {
        if ($this->availableLandmarksCache !== null) {
            return $this->availableLandmarksCache;
        }

        if ($this->isSiteManager()) {
            $managerUid = $this->managerUid();

            $landmarks = array_values(array_map(
                fn (array $landmark): array => [
                    'id' => (string) $landmark['id'],
                    'name' => trim((string) ($landmark['name'] ?? '')) ?: 'Managed landmark',
                    'manager_uid' => $managerUid,
                ],
                $this->siteManagerReadModel->landmarks($managerUid)
            ));
            usort($landmarks, fn (array $left, array $right): int => strcasecmp($left['name'], $right['name']));

            return $this->availableLandmarksCache = $landmarks;
        }

        return $this->availableLandmarksCache = [$this->assignedLandmark()];
    }

    private function curatorManagerUidFromProfile(): string
    {
        $uid = trim((string) Session::get('uid', ''));
        if ($uid === '') {
            return '';
        }

        return Cache::remember('curator:profile-manager:'.$uid, now()->addMinutes(10), function () use ($uid): string {
            $snapshot = $this->firebase->userDocument($uid, 'curator')->snapshot();
            if (! $snapshot->exists()) {
                return '';
            }

            return trim((string) ($snapshot->data()['created_by_manager_uid'] ?? ''));
        });
    }

    private function exhibitSnapshot(string $id)
    {
        $snapshot = $this->firebase->firestore()->collection('exhibits')->document($id)->snapshot();
        if (! $snapshot->exists()) {
            abort(404);
        }
        if (! $this->canManageLandmark(trim((string) ($snapshot->data()['landmark_id'] ?? '')))) {
            abort(403);
        }

        return $snapshot;
    }

    private function validator(Request $request, bool $creating, string $managerUid)
    {
        $categoryOptions = $this->activeCategoryNamesForManager($managerUid);
        $rules = [
            'name' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', 'max:120', Rule::in($categoryOptions)],
            'description' => ['nullable', 'string', 'max:5000'],
            'historical_info' => ['nullable', 'string', 'max:8000'],
            'year_period' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:active,inactive'],
            'images' => [$creating ? 'required' : 'nullable', 'array', 'max:8'],
            'images.*' => ['image', 'max:5120'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['string'],
        ];

        if ($this->isSiteManager()) {
            $rules['landmark_id'] = ['required', 'string', Rule::in(array_column($this->availableLandmarks(), 'id'))];
        }

        return Validator::make($request->all(), $rules, [
            'images.required' => 'Upload at least one exhibit image.',
            'images.*.max' => 'Each exhibit image must be 5 MB or smaller.',
            'category.in' => 'Choose an active exhibit category created by your Site Manager.',
            'landmark_id.required' => 'Choose the managed landmark this exhibit belongs to.',
            'landmark_id.in' => 'Choose a landmark you manage.',
        ]);
    }

    /** @return list<string> */
    private function activeCategoryNamesForManager(string $managerUid): array
    {
        $managerUid = trim($managerUid);
        if ($managerUid === '') {
            return [];
        }

        $allCategories = Cache::remember('exhibit-categories:manager:'.$managerUid, now()->addMinutes(10), function () use ($managerUid): array {
            $start = microtime(true);
            $categories = [];
            $documents = $this->firebase->firestore()
                ->collection('exhibit_categories')
                ->where('manager_uid', '==', $managerUid)
                ->documents();

            foreach ($documents as $document) {
                if (! $document->exists()) {
                    continue;
                }

                $data = $document->data();
                $categories[] = [
                    'id' => $document->id(),
                    'name' => trim((string) ($data['name'] ?? '')),
                    'status' => strtolower((string) ($data['status'] ?? 'active')) === 'inactive' ? 'inactive' : 'active',
                    'created_at' => (string) ($data['created_at'] ?? ''),
                    'updated_at' => (string) ($data['updated_at'] ?? ''),
                ];
            }

            Log::info('Timing Firestore query', [
                'query' => 'exhibit_categories.by_manager_uid',
                'manager_uid' => $managerUid,
                'records' => count($categories),
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
            ]);

            return $categories;
        });

        return $this->uniqueCategories(array_values(array_filter(array_map(
            fn (array $category): string => ($category['status'] ?? 'active') === 'active' ? (string) ($category['name'] ?? '') : '',
            $allCategories
        ))));
    }

    /** @param list<string> $categories @return list<string> */
    private function uniqueCategories(array $categories): array
    {
        $unique = [];
        foreach ($categories as $category) {
            $category = trim($category);
            if ($category === '') {
                continue;
            }

            $key = strtolower($category);
            if (! array_key_exists($key, $unique)) {
                $unique[$key] = $category;
            }
        }

        natcasesort($unique);

        return array_values($unique);
    }

    /** @return array<string, mixed> */
    private function payload(Request $request, array $landmark): array
    {
        return [
            'name' => (string) $request->input('name'),
            'category' => (string) $request->input('category'),
            'description' => (string) $request->input('description', ''),
            'historical_info' => (string) $request->input('historical_info', ''),
            'year_period' => (string) $request->input('year_period', ''),
            'status' => $request->input('status') === 'inactive' ? 'inactive' : 'active',
            'landmark_name' => $landmark['name'],
        ];
    }

    /** @return array{id:string,name:string,manager_uid:string} */
    private function writableLandmark(Request $request, ?string $fallbackLandmarkId = null): array
    {
        $landmarkId = $this->isSiteManager()
            ? trim((string) $request->input('landmark_id', $fallbackLandmarkId ?? ''))
            : (CuratorAssignedLandmark::id() ?? '');

        if (! $this->canManageLandmark($landmarkId)) {
            abort(403);
        }

        return $this->landmarkById($landmarkId);
    }

    /** @return array{id:string,name:string,manager_uid:string} */
    private function landmarkById(string $landmarkId): array
    {
        foreach ($this->availableLandmarks() as $landmark) {
            if ($landmark['id'] === $landmarkId) {
                return $landmark;
            }
        }

        abort(403);
    }

    private function canManageLandmark(string $landmarkId): bool
    {
        if ($landmarkId === '') {
            return false;
        }

        if ($this->isSiteManager()) {
            foreach ($this->availableLandmarks() as $landmark) {
                if ($landmark['id'] === $landmarkId) {
                    return true;
                }
            }

            return false;
        }

        return CuratorAssignedLandmark::canAccess($landmarkId);
    }

    private function managerUid(): string
    {
        if ($this->isSiteManager()) {
            return trim((string) Session::get('uid', ''));
        }

        return $this->assignedLandmark()['manager_uid'];
    }

    private function isSiteManager(): bool
    {
        return Session::get('role') === 'site_manager';
    }

    private function isCurator(): bool
    {
        return Session::get('role') === 'curator';
    }

    private function routePrefix(): string
    {
        return $this->isSiteManager() ? 'sitemanager' : 'curators';
    }

    private function routeName(string $name): string
    {
        return $this->routePrefix().'.'.$name;
    }

    /** @return list<\Illuminate\Http\UploadedFile> */
    private function uploadedFiles(Request $request, string $key): array
    {
        $files = $request->file($key, []);
        if ($files instanceof \Illuminate\Http\UploadedFile) {
            return [$files];
        }

        return is_array($files) ? array_values($files) : [];
    }

    private function exhibitDocumentsForLandmarks(array $landmarkIds, ?string $sortField = null, ?string $sortDirection = 'asc'): array
    {
        $landmarkIds = array_values(array_filter(array_unique(array_map(
            fn (mixed $id): string => trim((string) $id),
            $landmarkIds
        ))));
        if ($landmarkIds === []) {
            return [];
        }

        $cacheKey = 'exhibits:landmarks:v2:'.md5(implode('|', $landmarkIds));
        $records = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($landmarkIds): array {
            $start = microtime(true);
            $collection = $this->firebase->firestore()->collection('exhibits');
            $documents = [];
            if (count($landmarkIds) === 1) {
                $documents = iterator_to_array(
                    $collection->where('landmark_id', '==', $landmarkIds[0])->documents()
                );
            } else {
                foreach (array_chunk($landmarkIds, 30) as $chunk) {
                    foreach ($collection->where('landmark_id', 'in', $chunk)->documents() as $document) {
                        $documents[] = $document;
                    }
                }
            }

            Log::info('Timing Firestore query', [
                'query' => 'exhibits.by_landmark_id',
                'landmark_count' => count($landmarkIds),
                'documents' => count($documents),
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
            ]);

            return array_map(fn (mixed $document): array => [
                'id' => $document->id(),
                'data' => $document->data(),
            ], $documents);
        });

        return array_map(
            fn (array $record): ArrayDocumentSnapshot => new ArrayDocumentSnapshot((string) $record['id'], $record['data']),
            $records
        );
    }

    /** @param list<string> $landmarkIds */
    private function forgetExhibitDocumentsCache(array $landmarkIds): void
    {
        $ids = array_values(array_filter(array_unique(array_map('strval', $landmarkIds))));
        if ($ids !== []) {
            Cache::forget('exhibits:landmarks:v2:'.md5(implode('|', $ids)));
        }
    }
}
