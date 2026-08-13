<?php

namespace App\Http\Controllers\SiteManager;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use App\Support\CuratorAssignedLandmark;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ExhibitCategoryController extends Controller
{
    public function __construct(private FirebaseService $firebase) {}

    public function index(Request $request, ?string $id = null)
    {
        $categories = $this->categoriesForManager($this->managerUid());
        $perPage = 7;
        $page = max(1, (int) $request->query('page', 1));

        if ($id !== null) {
            $selectedIndex = array_search($id, array_column($categories, 'id'), true);
            if ($selectedIndex === false) {
                return redirect()->route($this->routeName('exhibit-categories.index'))
                    ->with('status_err', 'Exhibit category not found.');
            }
            $page = (int) floor($selectedIndex / $perPage) + 1;
        }

        $lastPage = max(1, (int) ceil(count($categories) / $perPage));
        $page = min($page, $lastPage);

        return view('sitemanager.exhibit-categories.index', [
            'categories' => new LengthAwarePaginator(
                array_slice($categories, ($page - 1) * $perPage, $perPage),
                count($categories),
                $perPage,
                $page,
                [
                    'path' => route($this->routeName('exhibit-categories.index')),
                    'query' => $request->except('page'),
                ]
            ),
            'routePrefix' => $this->routePrefix(),
            'openViewId' => $id,
        ]);
    }

    public function store(Request $request)
    {
        $managerUid = $this->managerUid();
        $validator = $this->validator($request);
        if ($validator->fails()) {
            return redirect()->route($this->routeName('exhibit-categories.index'))
                ->withErrors($validator)
                ->withInput();
        }

        $name = $this->categoryName($request);
        if ($this->categoryNameExists($managerUid, $name)) {
            return redirect()->route($this->routeName('exhibit-categories.index'))
                ->withErrors(['name' => 'You already have an exhibit category with this name.'])
                ->withInput();
        }

        $id = Str::random(20);
        $this->firebase->firestore()->collection('exhibit_categories')->document($id)->set([
            'name' => $name,
            'status' => 'active',
            'manager_uid' => $managerUid,
            'created_by_email' => (string) Session::get('email', ''),
            'created_by_role' => (string) Session::get('role', ''),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        $this->forgetCategoryCache($managerUid);

        return redirect()->route($this->routeName('exhibit-categories.index'))
            ->with('status', 'Exhibit category added successfully.');
    }

    public function update(Request $request, string $id)
    {
        $managerUid = $this->managerUid();
        $snapshot = $this->categorySnapshotForManager($id, $managerUid);
        if ($snapshot === null) {
            return redirect()->route($this->routeName('exhibit-categories.index'))
                ->with('status_err', 'Exhibit category not found.');
        }

        $validator = $this->validator($request);
        if ($validator->fails()) {
            return redirect()->route($this->routeName('exhibit-categories.index'), ['edit' => $id])
                ->withErrors($validator)
                ->withInput();
        }

        $name = $this->categoryName($request);
        if ($this->categoryNameExists($managerUid, $name, $id)) {
            return redirect()->route($this->routeName('exhibit-categories.index'), ['edit' => $id])
                ->withErrors(['name' => 'You already have an exhibit category with this name.'])
                ->withInput();
        }

        $this->firebase->firestore()->collection('exhibit_categories')->document($id)->set([
            'name' => $name,
            'status' => $this->status($request),
            'updated_at' => now()->toDateTimeString(),
        ], ['merge' => true]);

        $this->forgetCategoryCache($managerUid);

        return redirect()->route($this->routeName('exhibit-categories.index'))
            ->with('status', 'Exhibit category updated successfully.');
    }

    public function destroy(string $id)
    {
        $managerUid = $this->managerUid();
        $snapshot = $this->categorySnapshotForManager($id, $managerUid);
        if ($snapshot === null) {
            return redirect()->route($this->routeName('exhibit-categories.index'))
                ->with('status_err', 'Exhibit category not found.');
        }

        $this->firebase->firestore()->collection('exhibit_categories')->document($id)->delete();
        $this->forgetCategoryCache($managerUid);

        return redirect()->route($this->routeName('exhibit-categories.index'))
            ->with('status', 'Exhibit category deleted successfully.');
    }

    /** @return list<array{id:string,name:string,status:string,created_at:string,updated_at:string}> */
    private function categoriesForManager(string $managerUid): array
    {
        return Cache::remember('exhibit-categories:manager:'.$managerUid, now()->addMinutes(10), function () use ($managerUid): array {
            $start = microtime(true);
            $categories = [];
            foreach ($this->firebase->firestore()->collection('exhibit_categories')->where('manager_uid', '==', $managerUid)->documents() as $document) {
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

            usort($categories, fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

            Log::info('Timing Firestore query', [
                'query' => 'exhibit_categories.by_manager_uid',
                'manager_uid' => $managerUid,
                'records' => count($categories),
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
            ]);

            return $categories;
        });
    }

    private function categorySnapshotForManager(string $id, string $managerUid)
    {
        $snapshot = $this->firebase->firestore()->collection('exhibit_categories')->document($id)->snapshot();
        if (! $snapshot->exists()) {
            return null;
        }

        $ownerUid = trim((string) ($snapshot->data()['manager_uid'] ?? ''));

        return $ownerUid !== '' && $ownerUid === $managerUid ? $snapshot : null;
    }

    private function categoryNameExists(string $managerUid, string $name, ?string $exceptId = null): bool
    {
        $target = strtolower($name);
        foreach ($this->categoriesForManager($managerUid) as $category) {
            if ($exceptId !== null && $category['id'] === $exceptId) {
                continue;
            }

            if (strtolower($category['name']) === $target) {
                return true;
            }
        }

        return false;
    }

    private function validator(Request $request)
    {
        return Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
    }

    private function categoryName(Request $request): string
    {
        return trim((string) $request->input('name', ''));
    }

    private function status(Request $request): string
    {
        return $request->input('status') === 'inactive' ? 'inactive' : 'active';
    }

    private function managerUid(): string
    {
        if (Session::get('role') === 'curator') {
            $landmarkId = CuratorAssignedLandmark::id();
            if ($landmarkId !== null) {
                $managerUid = Cache::remember('curator:landmark-manager:'.$landmarkId, now()->addMinutes(10), function () use ($landmarkId): string {
                    $snapshot = $this->firebase->firestore()->collection('landmarks')->document($landmarkId)->snapshot();
                    if ($snapshot->exists()) {
                        return trim((string) ($snapshot->data()['manager_uid'] ?? $snapshot->data()['managerUid'] ?? ''));
                    }
                    return '';
                });
                if ($managerUid !== '') {
                    return $managerUid;
                }
            }

            $uid = trim((string) Session::get('uid', ''));
            if ($uid !== '') {
                $snapshot = $this->firebase->userDocument($uid, 'curator')->snapshot();
                if ($snapshot->exists()) {
                    $managerUid = trim((string) ($snapshot->data()['created_by_manager_uid'] ?? ''));
                    if ($managerUid !== '') {
                        return $managerUid;
                    }
                }
            }

            abort(403);
        }

        $managerUid = trim((string) Session::get('uid', ''));
        if ($managerUid === '') {
            abort(403);
        }

        return $managerUid;
    }

    private function routePrefix(): string
    {
        return Session::get('role') === 'curator' ? 'curators' : 'sitemanager';
    }

    private function routeName(string $name): string
    {
        return $this->routePrefix().'.'.$name;
    }

    private function forgetCategoryCache(string $managerUid): void
    {
        if ($managerUid !== '') {
            Cache::forget('exhibit-categories:manager:'.$managerUid);
        }
    }
}
