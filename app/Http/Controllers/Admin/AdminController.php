<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CuratorAccessibleLandmarks;
use App\Services\FirebaseService;
use App\Services\LandmarkEngagement;
use App\Services\QuizResultService;
use App\Services\SiteManagerLandmarks;
use App\Services\SiteManagerReadModel;
use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\FieldValue;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Auth;
use App\Support\ArrayDocumentSnapshot;
use App\Support\FirestoreBool;
use App\Support\LandmarkActivation;
use App\Support\LandmarkApprovalOrder;
use App\Support\SiteManagerDashboardStatistics;
use App\Support\UserApprovalPolicy;

class AdminController extends Controller
{
    private const LANDMARK_APPROVAL_CACHE_KEY = 'landmarks.approval.records';

    protected ?Auth $auth = null;

    protected ?FirestoreClient $firestore = null;

    protected FirebaseService $firebase;

    /** Route name for the users listing in the panel the current session uses. */
    protected function usersIndexRouteName(?Request $request = null): string
    {
        $request = $request ?? request();

        if ($request->session()->get('role') === 'admin') {
            return $request->input('return_to') === 'site-managers'
                ? 'admin.site-managers'
                : 'admin.users';
        }

        return 'sitemanager.curators';
    }

    public function __construct(
        FirebaseService $firebaseService,
        protected LandmarkEngagement $engagement,
        protected QuizResultService $quizResults,
        protected SiteManagerReadModel $siteManagerReadModel
    ) {
        $this->firebase = $firebaseService;
    }

    private function auth(): Auth
    {
        return $this->auth ??= $this->firebase->auth();
    }

    private function firestore(): FirestoreClient
    {
        return $this->firestore ??= $this->firebase->firestore();
    }

    public function dashboard()
    {
        $isSystemAdmin = request()->session()->get('role') === 'admin';
        $sessionUid = (string) request()->session()->get('uid', '');

        if (! $isSystemAdmin) {
            return $this->siteManagerDashboard($sessionUid);
        }

        $firestore = $this->firestore();

        $users = $this->firebase->allUserProfiles();
        Log::info('Dashboard user analytics using Firestore visitor collection.', [
            'visitor_collection' => $this->firebase->userCollectionPath('visitor'),
        ]);

        $userCount = 0;
        $curatorCount = 0;
        $adminCount = 0;
        $visitorCount = 0;
        $siteManagerProfiles = [];
        $curatorProfiles = [];

        foreach ($users as $uid => $user) {
            $role = strtolower((string) ($user['role'] ?? ''));
            $userCount++;
            if ($role === 'curator') {
                $curatorCount++;
                $curatorProfiles[(string) $uid] = $user + ['uid' => (string) $uid];
            }
            if ($role === 'admin') {
                $adminCount++;
            }
            if ($role === 'visitor') {
                $visitorCount++;
            }
            if ($role === 'site_manager') {
                $siteManagerProfiles[(string) $uid] = $user + ['uid' => (string) $uid];
            }
        }

        $landmarkCount = 0;
        $assignedLandmarkCount = 0;
        $unassignedLandmarkCount = 0;
        $managedLandmarkIds = [];
        $managedLandmarkNames = [];
        $landmarksById = [];
        foreach ($firestore->collection('landmarks')->documents() as $doc) {
            if ($doc->exists()) {
                $landmarkCount++;
                $data = $doc->data();
                $managerUid = trim((string) ($data['manager_uid'] ?? $data['managerUid'] ?? ''));
                $landmarksById[$doc->id()] = [
                    'id' => $doc->id(),
                    'name' => trim((string) ($data['name'] ?? '')) ?: 'Unnamed landmark',
                    'manager_uid' => $managerUid,
                ];
                if (! $isSystemAdmin && $sessionUid !== '' && $managerUid === $sessionUid) {
                    $managedLandmarkNames[$doc->id()] = trim((string) ($data['name'] ?? '')) ?: 'Unnamed landmark';
                }
            }
        }

        if (! $isSystemAdmin && $sessionUid !== '') {
            $managedLandmarkIds = $this->landmarkIdsManagedBy($sessionUid);
            $managedSet = array_flip($managedLandmarkIds);
            $landmarkCount = count($managedLandmarkIds);
            $assignedLandmarkSet = [];

            $curatorCount = count(array_filter($users, function ($user) use ($managedSet, &$assignedLandmarkSet) {
                $d = is_array($user) ? $user : [];
                if (($d['role'] ?? '') !== 'curator') {
                    return false;
                }
                $lid = trim((string) ($d['assigned_landmark_id'] ?? ''));
                if ($lid !== '' && isset($managedSet[$lid])) {
                    $assignedLandmarkSet[$lid] = true;

                    return true;
                }

                return false;
            }));

            $assignedLandmarkCount = count($assignedLandmarkSet);
            $unassignedLandmarkCount = max(0, $landmarkCount - $assignedLandmarkCount);
        }

        $visitsByDayValues = [];
        $visitsByDayLabels = [];
        $siteManagerStatistics = null;
        $topPerformers = [
            'site_managers' => [],
            'curators' => [],
            'has_visitor_data' => false,
        ];

        if ($isSystemAdmin) {
            $activity = $this->engagement->analyticsForLandmarks(array_keys($landmarksById));
            $visitsByDayLabels = $activity['totals']['daily_labels'];
            $visitsByDayValues = $activity['totals']['daily_values'];
            $topPerformers = $this->dashboardTopPerformers($landmarksById, $siteManagerProfiles, $curatorProfiles);
        } elseif ($sessionUid !== '') {
            $activity = $this->engagement->analyticsForLandmarks($managedLandmarkIds);
            $siteManagerStatistics = SiteManagerDashboardStatistics::fromRecords(
                array_merge($activity['records'], $this->quizResults->forLandmarks($managedLandmarkIds)),
                $managedLandmarkNames,
                null,
                (int) ($activity['totals']['visitor_users'] ?? 0)
            );
        }

        $view = $isSystemAdmin ? 'admin.dashboard' : 'sitemanager.dashboard';

        return view($view, [
            'userCount' => $userCount,
            'curatorCount' => $curatorCount,
            'adminCount' => $adminCount,
            'visitorCount' => $visitorCount,
            'landmarkCount' => $landmarkCount,
            'assignedLandmarkCount' => $assignedLandmarkCount,
            'unassignedLandmarkCount' => $unassignedLandmarkCount,
            'visitsByDay' => $visitsByDayValues,
            'visitsByDayLabels' => $visitsByDayLabels,
            'showSystemInsights' => $isSystemAdmin,
            'siteManagerStatistics' => $siteManagerStatistics,
            'topPerformers' => $topPerformers,
        ]);
    }

    /**
     * @param  array<string, array{id:string,name:string,manager_uid:string}>  $landmarksById
     * @param  array<string, array<string, mixed>>  $siteManagerProfiles
     * @param  array<string, array<string, mixed>>  $curatorProfiles
     * @return array{site_managers:list<array<string,mixed>>,curators:list<array<string,mixed>>,has_visitor_data:bool}
     */
    private function dashboardTopPerformers(array $landmarksById, array $siteManagerProfiles, array $curatorProfiles): array
    {
        if ($landmarksById === []) {
            return [
                'site_managers' => [],
                'curators' => [],
                'has_visitor_data' => false,
            ];
        }

        $activity = $this->engagement->analyticsForLandmarks(array_keys($landmarksById));
        $visitorsByLandmark = [];
        foreach ($activity['records'] as $record) {
            $landmarkId = trim((string) ($record['landmark_id'] ?? ''));
            if ($landmarkId === '' || ! isset($landmarksById[$landmarkId])) {
                continue;
            }

            $visitorsByLandmark[$landmarkId] = ($visitorsByLandmark[$landmarkId] ?? 0)
                + max(1, (int) ($record['visit_count'] ?? 1));
        }

        $hasVisitorData = array_sum($visitorsByLandmark) > 0;
        if (! $hasVisitorData) {
            return [
                'site_managers' => [],
                'curators' => [],
                'has_visitor_data' => false,
            ];
        }

        $managerRows = [];
        foreach ($landmarksById as $landmarkId => $landmark) {
            $managerUid = trim((string) ($landmark['manager_uid'] ?? ''));
            if ($managerUid === '') {
                continue;
            }

            $managerRows[$managerUid] ??= [
                'site_manager' => $this->dashboardProfileName($siteManagerProfiles[$managerUid] ?? [], 'Site Manager'),
                'managed_landmarks' => 0,
                'total_visitors' => 0,
            ];
            $managerRows[$managerUid]['managed_landmarks']++;
            $managerRows[$managerUid]['total_visitors'] += (int) ($visitorsByLandmark[$landmarkId] ?? 0);
        }

        $curatorRows = [];
        foreach ($curatorProfiles as $curator) {
            $landmarkId = trim((string) ($curator['assigned_landmark_id'] ?? ''));
            if ($landmarkId === '' || ! isset($landmarksById[$landmarkId])) {
                continue;
            }

            $curatorRows[] = [
                'curator' => $this->dashboardProfileName($curator, 'Curator'),
                'assigned_landmark' => $landmarksById[$landmarkId]['name'],
                'total_visitors' => (int) ($visitorsByLandmark[$landmarkId] ?? 0),
            ];
        }

        $managerRows = array_values(array_filter($managerRows, fn (array $row): bool => (int) $row['total_visitors'] > 0));
        $curatorRows = array_values(array_filter($curatorRows, fn (array $row): bool => (int) $row['total_visitors'] > 0));

        usort($managerRows, fn (array $a, array $b): int => ((int) $b['total_visitors'] <=> (int) $a['total_visitors'])
            ?: strcasecmp((string) $a['site_manager'], (string) $b['site_manager']));
        usort($curatorRows, fn (array $a, array $b): int => ((int) $b['total_visitors'] <=> (int) $a['total_visitors'])
            ?: strcasecmp((string) $a['curator'], (string) $b['curator']));

        return [
            'site_managers' => array_slice($managerRows, 0, 2),
            'curators' => array_slice($curatorRows, 0, 3),
            'has_visitor_data' => true,
        ];
    }

    /** @param  array<string, mixed>  $profile */
    private function dashboardProfileName(array $profile, string $fallback): string
    {
        $name = trim((string) ($profile['name'] ?? $profile['displayName'] ?? ''));
        if ($name === '') {
            $name = trim(implode(' ', array_filter([
                trim((string) ($profile['first_name'] ?? '')),
                trim((string) ($profile['last_name'] ?? '')),
            ])));
        }
        if ($name === '') {
            $name = trim((string) ($profile['email'] ?? ''));
        }

        return $name !== '' ? $name : $fallback;
    }

    public function map()
    {
        $landmarks = [];
        $mapCategories = [];
        $mapCities = ['Carcar City', 'Cebu City', 'Lapu-Lapu City', 'Mandaue City', 'Talisay City'];

        foreach ($this->firestore()->collection('landmarks')->documents() as $landmark) {
            if (! $landmark->exists()) {
                continue;
            }

            $data = $landmark->data();
            $lat = $data['latitude'] ?? $data['lati'] ?? null;
            $lng = $data['longitude'] ?? $data['longti'] ?? null;

            if (! is_numeric($lat) || ! is_numeric($lng)) {
                continue;
            }

            $landmarkId = (string) $landmark->id();
            $category = trim((string) ($data['category'] ?? ''));
            $city = $this->adminMapCity($data, (float) $lat, (float) $lng);
            $imageSrc = '';
            if (! empty($data['image_path'] ?? null)) {
                $imageSrc = (string) $data['image_path'];
            }

            $landmarks[] = [
                'id' => $landmarkId,
                'name' => (string) ($data['name'] ?? 'Untitled'),
                'status' => (string) ($data['activation_status'] ?? 'active'),
                'location' => (string) ($data['location'] ?? ''),
                'category' => $category,
                'city' => $city,
                'description' => (string) ($data['description'] ?? ''),
                'imageSrc' => $imageSrc,
                'detailsUrl' => route('admin.landmarks.show', $landmarkId),
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
            ];

            if ($category !== '') {
                $mapCategories[strtolower($category)] = $category;
            }
        }

        $mapCategories = array_values($mapCategories);
        $mapCities = array_values($mapCities);
        natcasesort($mapCategories);
        natcasesort($mapCities);

        return view('sitemanager.map', [
            'landmarks' => $landmarks,
            'mapCategories' => array_values($mapCategories),
            'mapCities' => array_values($mapCities),
            'enableUserLocation' => true,
            'mapboxToken' => config('services.mapbox.token'),
            'mapEmptyMessage' => 'No landmarks with coordinates found.',
        ]);
    }

    /** @param  array<string, mixed>  $data */
    private function adminMapCity(array $data, float $latitude, float $longitude): string
    {
        $supportedCities = [
            'carcar city' => 'Carcar City',
            'cebu city' => 'Cebu City',
            'lapu lapu city' => 'Lapu-Lapu City',
            'mandaue city' => 'Mandaue City',
            'talisay city' => 'Talisay City',
        ];
        $address = is_array($data['address'] ?? null) ? $data['address'] : [];
        $candidates = [
            $data['city'] ?? null,
            $data['city_name'] ?? null,
            $data['cityName'] ?? null,
            $data['municipality'] ?? null,
            $data['address_city'] ?? null,
            $data['addressCity'] ?? null,
            $data['location_city'] ?? null,
            $data['locality'] ?? null,
            $address['city'] ?? null,
            $address['municipality'] ?? null,
            $address['locality'] ?? null,
            is_string($data['address'] ?? null) ? $data['address'] : null,
            $data['location'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (! is_scalar($candidate)) {
                continue;
            }

            $normalized = strtolower(trim((string) $candidate));
            $normalized = str_replace(['–', '—', '-', '_'], ' ', $normalized);
            $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

            foreach ($supportedCities as $cityKey => $cityLabel) {
                if ($normalized === $cityKey || str_contains($normalized, $cityKey)) {
                    return $cityLabel;
                }
            }
        }

        $cityCenters = [
            'Carcar City' => [10.1060, 123.6402],
            'Cebu City' => [10.3157, 123.8854],
            'Lapu-Lapu City' => [10.3103, 123.9494],
            'Mandaue City' => [10.3236, 123.9222],
            'Talisay City' => [10.2447, 123.8494],
        ];
        $nearestCity = '';
        $nearestDistance = INF;

        foreach ($cityCenters as $cityLabel => [$cityLatitude, $cityLongitude]) {
            $latitudeDistance = $latitude - $cityLatitude;
            $longitudeDistance = ($longitude - $cityLongitude) * cos(deg2rad($latitude));
            $distance = ($latitudeDistance ** 2) + ($longitudeDistance ** 2);

            if ($distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearestCity = $cityLabel;
            }
        }

        return $nearestDistance <= 0.0225 ? $nearestCity : '';
    }

    private function siteManagerDashboard(string $managerUid)
    {
        $forceRefresh = request()->boolean('refresh');
        if ($forceRefresh) {
            Cache::forget($this->siteManagerReadModel->dashboardKey($managerUid));
        }

        $data = Cache::remember(
            $this->siteManagerReadModel->dashboardKey($managerUid),
            now()->addMinutes(5),
            function () use ($managerUid, $forceRefresh) {
                $landmarks = $this->siteManagerReadModel->landmarks($managerUid);
                $landmarkIds = array_column($landmarks, 'id');
                if ($forceRefresh) {
                    $this->engagement->forgetAnalyticsForLandmarks($landmarkIds);
                }
                $landmarkNames = [];
                foreach ($landmarks as $landmark) {
                    $landmarkNames[$landmark['id']] = trim((string) ($landmark['name'] ?? '')) ?: 'Unnamed landmark';
                }

                $activity = $this->engagement->analyticsForLandmarks($landmarkIds);
                $statistics = SiteManagerDashboardStatistics::fromRecords(
                    array_merge($activity['records'], $this->quizResults->forLandmarks($landmarkIds)),
                    $landmarkNames,
                    null,
                    (int) ($activity['totals']['visitor_users'] ?? 0)
                );
                $curators = $this->siteManagerReadModel->curators($managerUid);

                return [
                    'landmarkCount' => count($landmarks),
                    'curatorCount' => count($curators),
                    'managedLandmarks' => $this->siteManagerDashboardLandmarks(
                        $landmarks,
                        $curators,
                        $statistics['visitors_by_landmark'] ?? []
                    ),
                    'siteManagerStatistics' => $statistics,
                ];
            }
        );

        return view('sitemanager.dashboard', $data);
    }

    /**
     * @param  list<array<string, mixed>>  $landmarks
     * @param  list<array<string, mixed>>  $curators
     * @param  array<string, int>  $visitorCounts
     * @return list<array<string, mixed>>
     */
    private function siteManagerDashboardLandmarks(array $landmarks, array $curators, array $visitorCounts): array
    {
        $curatorsByLandmark = [];
        foreach ($curators as $curator) {
            $landmarkId = trim((string) ($curator['assigned_landmark_id'] ?? ''));
            if ($landmarkId === '') {
                continue;
            }

            $name = trim((string) ($curator['name'] ?? ''));
            if ($name === '') {
                $fullName = trim(implode(' ', array_filter([
                    trim((string) ($curator['first_name'] ?? '')),
                    trim((string) ($curator['last_name'] ?? '')),
                ])));
                $name = $fullName !== '' ? $fullName : trim((string) ($curator['email'] ?? 'Curator'));
            }

            $curatorsByLandmark[$landmarkId][] = $name !== '' ? $name : 'Curator';
        }

        $items = [];
        foreach ($landmarks as $landmark) {
            $landmarkId = trim((string) ($landmark['id'] ?? ''));
            if ($landmarkId === '') {
                continue;
            }

            $assignedCurators = $curatorsByLandmark[$landmarkId] ?? [];
            $imagePath = '';
            if (! empty($landmark['image_path'] ?? null)) {
                $imagePath = (string) $landmark['image_path'];
            }

            $status = strtolower(trim((string) ($landmark['activation_status'] ?? 'active')));
            $items[] = [
                'id' => $landmarkId,
                'name' => trim((string) ($landmark['name'] ?? '')) ?: 'Untitled landmark',
                'image_path' => $imagePath,
                'visitor_count' => (int) ($visitorCounts[$landmarkId] ?? 0),
                'assigned_curator' => $this->siteManagerCuratorSummary($assignedCurators),
                'status' => LandmarkActivation::label($status),
                'status_key' => $status !== '' ? $status : 'active',
                'url' => route('sitemanager.landmarks.show', ['id' => $landmarkId]),
            ];
        }

        usort($items, fn (array $a, array $b): int => strcasecmp((string) $a['name'], (string) $b['name']));

        return $items;
    }

    /** @param  list<string>  $assignedCurators */
    private function siteManagerCuratorSummary(array $assignedCurators): string
    {
        $assignedCurators = array_values(array_unique(array_filter($assignedCurators)));
        if ($assignedCurators === []) {
            return 'Unassigned';
        }

        if (count($assignedCurators) === 1) {
            return $assignedCurators[0];
        }

        return $assignedCurators[0].' +'.(count($assignedCurators) - 1).' more';
    }

    public function dashboardRoleUsage()
    {
        $counts = [
            'admins' => 0,
            'curators' => 0,
            'visitors' => 0,
        ];

        foreach ($this->firebase->allUserProfiles() as $user) {
            $role = strtolower((string) ($user['role'] ?? ''));

            if ($role === 'admin') {
                $counts['admins']++;
            } elseif ($role === 'curator') {
                $counts['curators']++;
            } elseif ($role === 'visitor') {
                $counts['visitors']++;
            }
        }

        return response()->json($counts);
    }

    public function users(Request $request)
    {
        $search = strtolower(trim((string) $request->input('search', '')));
        $roleFilter = strtolower(trim((string) $request->input('role', '')));
        $userSort = strtolower(trim((string) $request->query('sort', '')));
        $userSortDirection = strtolower(trim((string) $request->query('direction', 'asc')));
        $curatorsOnly = $request->routeIs('sitemanager.curators');
        $siteManagersOnly = $request->routeIs('admin.site-managers');
        $adminUsersIndex = $request->routeIs('admin.users');
        if (! $adminUsersIndex || ! in_array($userSort, ['email', 'role'], true)) {
            $userSort = null;
            $userSortDirection = null;
        } elseif (! in_array($userSortDirection, ['asc', 'desc'], true)) {
            $userSortDirection = 'asc';
        }
        if ($adminUsersIndex && ! in_array($roleFilter, ['', 'admin', 'curator', 'site_manager'], true)) {
            $roleFilter = '';
        }
        if ($curatorsOnly) {
            $roleFilter = 'curator';
        }
        if ($siteManagersOnly) {
            $roleFilter = 'site_manager';
        }

        $sessionRole = (string) $request->session()->get('role', '');
        $sessionUid = (string) $request->session()->get('uid', '');

        if ($curatorsOnly && $sessionRole === 'site_manager') {
            return $this->siteManagerCurators($request, $sessionUid, $search);
        }

        $managedLandmarkSet = [];
        if ($curatorsOnly && $sessionRole === 'site_manager' && $sessionUid !== '') {
            foreach ($this->landmarkIdsManagedBy($sessionUid) as $mid) {
                $k = trim((string) $mid);
                if ($k !== '') {
                    $managedLandmarkSet[$k] = true;
                }
            }
        }

        $authUsers = iterator_to_array($this->auth()->listUsers());

        $firestoreProfiles = $this->firebase->allUserProfiles();
        Log::info('Admin visitor statistics using Firestore visitor collection.', [
            'visitor_collection' => $this->firebase->userCollectionPath('visitor'),
        ]);

        $mergedUsers = [];
        foreach ($authUsers as $user) {
            $role = strtolower($user->customClaims['role'] ?? '');
            $uid = $user->uid;
            $profile = $firestoreProfiles[$uid] ?? [];

            if (isset($profile['role'])) {
                $role = strtolower((string) $profile['role']);
            }

            if (! $role) {
                $role = 'visitor';
            }

            if ($adminUsersIndex && $role === 'visitor') {
                continue;
            }

            if ($siteManagersOnly && $role !== 'site_manager') {
                continue;
            }

            $visitorPatch = UserApprovalPolicy::visitorAutoApprovalPatch(array_merge($profile, ['role' => $role]));
            if ($visitorPatch !== null) {
                $this->firebase->userDocument($uid, $role)->set($visitorPatch, ['merge' => true]);
                $profile = array_merge($profile, $visitorPatch);
            }

            $requiresApproval = UserApprovalPolicy::effectiveRequiresApproval(
                $role,
                $profile['requires_approval'] ?? null
            );
            $approvalStatus = strtolower((string) ($profile['approval_status'] ?? 'approved'));
            if (! $requiresApproval) {
                $approvalStatus = 'approved';
            }

            $email = strtolower($user->email ?? '');
            $matchesSearch = ! $search || str_contains($email, $search) || str_contains($uid, $search);
            $matchesRole = ! $roleFilter || $role === $roleFilter;

            if ($matchesSearch && $matchesRole) {
                $assignedLandmarkId = isset($profile['assigned_landmark_id'])
                    ? (string) $profile['assigned_landmark_id']
                    : null;
                if ($assignedLandmarkId === '') {
                    $assignedLandmarkId = null;
                }

                $curatorRegistrationType = (string) ($profile['curator_registration_type'] ?? '');
                $credentialsDocument = is_array($profile['credentials_document'] ?? null)
                    ? $profile['credentials_document']
                    : null;
                $effectiveProfile = array_merge($profile, [
                    'role' => $role,
                    'assigned_landmark_id' => $assignedLandmarkId,
                    'curator_registration_type' => $curatorRegistrationType,
                    'credentials_document' => $credentialsDocument,
                ]);

                $approvalActions = $requiresApproval && $approvalStatus === 'pending'
                    && $this->actorMayApprovePendingUser($sessionRole, $sessionUid, $effectiveProfile);

                $mergedUsers[] = (object) [
                    'email' => $user->email,
                    'uid' => $uid,
                    'role' => $role,
                    'first_name' => (string) ($profile['first_name'] ?? ''),
                    'last_name' => (string) ($profile['last_name'] ?? ''),
                    'name' => (string) ($profile['name'] ?? ($user->displayName ?? '')),
                    'requires_approval' => $requiresApproval,
                    'approval_status' => $approvalStatus,
                    'account_status' => strtolower((string) ($profile['account_status'] ?? 'active')) === 'inactive'
                        ? 'inactive'
                        : 'active',
                    'curator_registration_type' => $curatorRegistrationType,
                    'assigned_landmark_id' => $assignedLandmarkId,
                    'approval_actions' => $approvalActions,
                    'credentials_document' => $credentialsDocument,
                ];
            }
        }

        if ($curatorsOnly && $sessionRole === 'site_manager') {
            $mergedUsers = array_values(array_filter($mergedUsers, function ($u) use ($managedLandmarkSet) {
                if ($u->role !== 'curator') {
                    return false;
                }
                $lidKey = trim((string) ($u->assigned_landmark_id ?? ''));

                return $lidKey !== '' && isset($managedLandmarkSet[$lidKey]);
            }));
        }

        $assignableLandmarks = [];
        if ($curatorsOnly && $sessionRole === 'site_manager' && $sessionUid !== '') {
            $assignmentLandmarks = app(SiteManagerLandmarks::class)->curatorAssignmentLandmarks($sessionUid);
            $assignableLandmarks = $assignmentLandmarks['assignable'];
            $landmarkNames = [];
            foreach ($assignableLandmarks as $landmark) {
                $landmarkNames[(string) $landmark['id']] = (string) $landmark['name'];
            }
            foreach ($mergedUsers as $mergedUser) {
                $mergedUser->assigned_landmark_name = $landmarkNames[(string) ($mergedUser->assigned_landmark_id ?? '')]
                    ?? 'Unassigned';
            }
        }

        $editCurator = null;
        $editUid = trim((string) $request->query('edit', ''));
        if ($curatorsOnly && $editUid !== '') {
            foreach ($mergedUsers as $mergedUser) {
                if ($mergedUser->uid === $editUid) {
                    $editCurator = $mergedUser;
                    break;
                }
            }
        }

        if ($adminUsersIndex) {
            usort($mergedUsers, static function (object $left, object $right) use ($userSort, $userSortDirection): int {
                $approvalPriority = (empty($left->approval_actions) ? 1 : 0)
                    <=> (empty($right->approval_actions) ? 1 : 0);

                $defaultComparison = $approvalPriority !== 0
                    ? $approvalPriority
                    : (strnatcasecmp((string) $left->email, (string) $right->email)
                        ?: strcmp((string) $left->uid, (string) $right->uid));

                if ($userSort === null) {
                    return $defaultComparison;
                }

                $leftValue = $userSort === 'role'
                    ? match ((string) $left->role) {
                        'admin' => 'Admin',
                        'curator' => 'Curator',
                        'site_manager' => 'Site Manager',
                        default => ucfirst((string) $left->role),
                    }
                    : (string) $left->email;
                $rightValue = $userSort === 'role'
                    ? match ((string) $right->role) {
                        'admin' => 'Admin',
                        'curator' => 'Curator',
                        'site_manager' => 'Site Manager',
                        default => ucfirst((string) $right->role),
                    }
                    : (string) $right->email;
                $comparison = strnatcasecmp($leftValue, $rightValue);

                return $comparison !== 0
                    ? ($userSortDirection === 'desc' ? -$comparison : $comparison)
                    : $defaultComparison;
            });
        }

        $usersForView = $mergedUsers;
        if ($request->routeIs('admin.users')) {
            $perPage = 6;
            $totalUsers = count($mergedUsers);
            $lastPage = max(1, (int) ceil($totalUsers / $perPage));
            $page = min(max(1, (int) $request->query('page', 1)), $lastPage);
            $usersForView = new LengthAwarePaginator(
                array_slice($mergedUsers, ($page - 1) * $perPage, $perPage),
                $totalUsers,
                $perPage,
                $page,
                [
                    'path' => route('admin.users'),
                    'query' => $request->except('page'),
                ]
            );
        }

        return view('admin.users', [
            'users' => $usersForView,
            'search' => $search,
            'roleFilter' => $roleFilter,
            'curatorsOnly' => $curatorsOnly,
            'siteManagersOnly' => $siteManagersOnly,
            'assignableLandmarks' => $assignableLandmarks,
            'editCurator' => $editCurator,
            'userSort' => $userSort,
            'userSortDirection' => $userSortDirection,
        ]);
    }

    private function siteManagerCurators(Request $request, string $managerUid, string $search)
    {
        $landmarks = $this->siteManagerReadModel->landmarks($managerUid);
        $landmarkNames = [];
        foreach ($landmarks as $landmark) {
            $landmarkNames[(string) $landmark['id']] = (string) ($landmark['name'] ?? 'Unnamed landmark');
        }
        $landmarkFilter = trim((string) $request->query('landmark', ''));
        if (! isset($landmarkNames[$landmarkFilter])) {
            $landmarkFilter = '';
        }
        $userSort = strtolower(trim((string) $request->query('sort', '')));
        $userSortDirection = strtolower(trim((string) $request->query('direction', 'asc')));
        if (! in_array($userSort, ['email', 'landmark'], true)) {
            $userSort = null;
            $userSortDirection = null;
        } elseif (! in_array($userSortDirection, ['asc', 'desc'], true)) {
            $userSortDirection = 'asc';
        }

        $users = [];
        foreach ($this->siteManagerReadModel->curators($managerUid) as $profile) {
            $uid = (string) $profile['uid'];
            $email = strtolower((string) ($profile['email'] ?? ''));
            $requiresApproval = UserApprovalPolicy::effectiveRequiresApproval(
                'curator',
                $profile['requires_approval'] ?? null
            );
            $approvalStatus = $requiresApproval
                ? strtolower((string) ($profile['approval_status'] ?? 'approved'))
                : 'approved';
            $assignedLandmarkId = trim((string) ($profile['assigned_landmark_id'] ?? ''));
            $assignedLandmarkName = $landmarkNames[$assignedLandmarkId] ?? 'Unassigned';
            $accountStatus = strtolower((string) ($profile['account_status'] ?? 'active')) === 'inactive' ? 'inactive' : 'active';

            if ($landmarkFilter !== '' && $assignedLandmarkId !== $landmarkFilter) {
                continue;
            }

            if ($search !== '') {
                $statusMatch = in_array($search, ['active', 'inactive'], true)
                    ? $accountStatus === $search
                    : str_contains($accountStatus, $search);

                if (! str_contains($email, $search)
                    && ! str_contains(strtolower($assignedLandmarkName), $search)
                    && ! $statusMatch) {
                    continue;
                }
            }

            $users[] = (object) [
                'email' => $profile['email'] ?? '',
                'uid' => $uid,
                'role' => 'curator',
                'first_name' => (string) ($profile['first_name'] ?? ''),
                'last_name' => (string) ($profile['last_name'] ?? ''),
                'name' => (string) ($profile['name'] ?? ''),
                'requires_approval' => $requiresApproval,
                'approval_status' => $approvalStatus,
                'account_status' => $accountStatus,
                'curator_registration_type' => (string) ($profile['curator_registration_type'] ?? ''),
                'assigned_landmark_id' => $assignedLandmarkId,
                'assigned_landmark_name' => $assignedLandmarkName,
                'approval_actions' => $requiresApproval && $approvalStatus === 'pending'
                    && ($profile['curator_registration_type'] ?? '') === 'existing_landmark'
                    && isset($landmarkNames[$assignedLandmarkId]),
            ];
        }

        usort($users, function (object $left, object $right) use ($userSort, $userSortDirection): int {
            if ($userSort !== null) {
                $field = $userSort === 'landmark' ? 'assigned_landmark_name' : 'email';
                $comparison = strnatcasecmp((string) $left->{$field}, (string) $right->{$field});
                if ($comparison !== 0) {
                    return $userSortDirection === 'desc' ? -$comparison : $comparison;
                }
            }

            $statusRank = static fn (string $status): int => $status === 'active' ? 1 : 2;
            $statusComparison = $statusRank((string) $left->account_status)
                <=> $statusRank((string) $right->account_status);

            if ($statusComparison !== 0) {
                return $statusComparison;
            }

            $emailComparison = strnatcasecmp((string) $left->email, (string) $right->email);

            return $emailComparison !== 0
                ? $emailComparison
                : strcmp((string) $left->uid, (string) $right->uid);
        });

        $editCurator = null;
        $editUid = trim((string) $request->query('edit', ''));
        foreach ($users as $user) {
            if ($user->uid === $editUid) {
                $editCurator = $user;
                break;
            }
        }

        $perPage = 5;
        $totalUsers = count($users);
        $lastPage = max(1, (int) ceil($totalUsers / $perPage));
        $page = min(max(1, (int) $request->query('page', 1)), $lastPage);
        $usersForView = new LengthAwarePaginator(
            array_slice($users, ($page - 1) * $perPage, $perPage),
            $totalUsers,
            $perPage,
            $page,
            [
                'path' => route('sitemanager.curators'),
                'query' => $request->except(['page', 'edit', 'create']),
            ]
        );

        $assignableLandmarks = app(SiteManagerLandmarks::class)->curatorAssignmentLandmarks($managerUid)['assignable'];

        return view('admin.users', [
            'users' => $usersForView,
            'search' => $search,
            'roleFilter' => 'curator',
            'curatorsOnly' => true,
            'siteManagersOnly' => false,
            'assignableLandmarks' => $assignableLandmarks,
            'curatorFilterLandmarks' => $landmarks,
            'landmarkFilter' => $landmarkFilter,
            'editCurator' => $editCurator,
            'userSort' => $userSort,
            'userSortDirection' => $userSortDirection,
        ]);
    }

    public function approveUser(Request $request, string $uid)
    {
        $profile = $this->firebase->userProfile($uid, $request->input('role'));

        if ($profile === null) {
            return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
                ->with('status_err', 'User profile not found in database.');
        }

        $data = $profile['data'];
        $targetRole = strtolower((string) ($data['role'] ?? 'visitor'));
        if (! UserApprovalPolicy::roleRequiresApproval($targetRole)) {
            return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
                ->with('status_err', 'Visitors do not require approval.');
        }

        if (! FirestoreBool::isTrue($data['requires_approval'] ?? null)) {
            return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
                ->with('status_err', 'This user does not require approval.');
        }

        $status = strtolower((string) ($data['approval_status'] ?? ''));
        if ($status !== 'pending') {
            return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
                ->with('status_err', 'Only pending accounts can be approved.');
        }

        $actorRole = (string) $request->session()->get('role', '');
        $actorUid = (string) $request->session()->get('uid', '');
        if (! $this->actorMayApprovePendingUser($actorRole, $actorUid, $data)) {
            return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
                ->with('status_err', 'You do not have permission to approve this account.');
        }

        $now = now()->toDateTimeString();

        try {
            $pendingProposalLandmarkId = null;
            if (($data['role'] ?? '') === 'curator' && ! empty($data['pending_landmark_id'])) {
                $pendingProposalLandmarkId = (string) $data['pending_landmark_id'];

                $landmarkRef = $this->firestore()->collection('landmarks')->document($pendingProposalLandmarkId);
                $lmSnap = $landmarkRef->snapshot();
                if (! $lmSnap->exists()) {
                    throw new \RuntimeException('The proposed landmark no longer exists. Reject this signup instead.');
                }

                $landmarkRef->set([
                    'activation_status' => 'active',
                    'activated_at' => $now,
                ], ['merge' => true]);
            }

            $docRef = $profile['ref'];
            $approvalPaths = [
                ['path' => 'approval_status', 'value' => 'approved'],
                ['path' => 'requires_approval', 'value' => false],
                ['path' => 'approved_at', 'value' => $now],
                ['path' => 'updated_at', 'value' => $now],
            ];

            if ($pendingProposalLandmarkId) {
                $approvalPaths[] = ['path' => 'assigned_landmark_id', 'value' => $pendingProposalLandmarkId];
                $approvalPaths[] = ['path' => 'pending_landmark_id', 'value' => FieldValue::deleteField()];
            }

            $docRef->update($approvalPaths);

            $after = $docRef->snapshot();
            if (! $after->exists()) {
                throw new \RuntimeException('Document missing after approve update.');
            }
            $freshStatus = strtolower((string) ($after->data()['approval_status'] ?? ''));
            if ($freshStatus !== 'approved') {
                throw new \RuntimeException(
                    'Firestore still shows approval_status "'.$freshStatus.'" after update (expected approved). Verify project ID matches this app\'s firebase_credentials.json.'
                );
            }

        } catch (\Throwable $e) {
            report($e);

            return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
                ->with('status_err', 'Approve failed: '.$e->getMessage());
        }
        if ($actorRole === 'site_manager') {
            $this->siteManagerReadModel->forget($actorUid);
        }

        return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
            ->with('status', 'User approved successfully.');
    }

    public function rejectUser(Request $request, string $uid)
    {
        $profile = $this->firebase->userProfile($uid, $request->input('role'));

        if ($profile === null) {
            return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
                ->with('status_err', 'User profile not found in database.');
        }

        $data = $profile['data'];
        $targetRole = strtolower((string) ($data['role'] ?? 'visitor'));
        if (! UserApprovalPolicy::roleRequiresApproval($targetRole)) {
            return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
                ->with('status_err', 'Visitors cannot be rejected through the approval workflow.');
        }

        if (! FirestoreBool::isTrue($data['requires_approval'] ?? null)) {
            return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
                ->with('status_err', 'This user does not require approval.');
        }

        $status = strtolower((string) ($data['approval_status'] ?? ''));
        if ($status !== 'pending') {
            return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
                ->with('status_err', 'Only pending accounts can be rejected.');
        }

        $actorRole = (string) $request->session()->get('role', '');
        $actorUid = (string) $request->session()->get('uid', '');
        if (! $this->actorMayApprovePendingUser($actorRole, $actorUid, $data)) {
            return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
                ->with('status_err', 'You do not have permission to reject this account.');
        }

        try {
            if (($data['role'] ?? '') === 'curator' && ! empty($data['pending_landmark_id'])) {
                $pendingProposalLandmarkId = (string) $data['pending_landmark_id'];
                if ($pendingProposalLandmarkId !== '') {
                    try {
                        $this->firestore()->collection('landmarks')->document($pendingProposalLandmarkId)->delete();
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }

            if ($actorRole === 'site_manager' && ($data['role'] ?? '') === 'curator') {
                $profile['ref']->set([
                    'approval_status' => 'rejected',
                    'requires_approval' => false,
                    'account_status' => 'inactive',
                    'rejected_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ], ['merge' => true]);
                $this->siteManagerReadModel->forget($actorUid);

                return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
                    ->with('status', 'Curator registration rejected and marked inactive.');
            }

            $this->auth()->deleteUser($uid);
            $profile['ref']->delete();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
                ->with('status_err', 'Could not reject user in Firebase: '.$e->getMessage());
        }

        return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
            ->with('status', 'Registration rejected successfully.');
    }

    public function landmarks(Request $request)
    {
        $open = trim((string) $request->query('open', ''));
        $sessionRole = (string) $request->session()->get('role', '');
        if ($open !== '' && $sessionRole === 'site_manager') {
            return redirect()->route('sitemanager.landmarks.show', array_filter([
                'id' => $open,
            ]));
        }
        if ($open !== '' && $sessionRole === 'admin') {
            return redirect()->route('admin.landmarks.show', array_filter([
                'id' => $open,
                'status' => $request->query('status'),
            ]));
        }
        if (in_array($sessionRole, ['site_manager', 'admin'], true) && $request->query->has('view')) {
            return redirect()->route($sessionRole === 'site_manager' ? 'sitemanager.landmarks' : 'admin.landmarks', $request->except('view'));
        }

        return $this->landmarksIndexView($request);
    }

    /**
     * @return \Illuminate\View\View
     */
    private function landmarksIndexView(Request $request, ?string $openLandmarkId = null)
    {
        $sessionRole = (string) $request->session()->get('role', '');
        $perPage = $sessionRole === 'admin' ? 7 : 7;
        $sessionUid = (string) $request->session()->get('uid', '');

        if ($sessionRole === 'site_manager' && $sessionUid !== '') {
            $allLandmarks = array_map(
                fn (array $landmark) => new ArrayDocumentSnapshot((string) $landmark['id'], $landmark),
                $this->siteManagerReadModel->landmarks($sessionUid)
            );
        } else {
            $records = Cache::remember(
                self::LANDMARK_APPROVAL_CACHE_KEY,
                now()->addSeconds(15),
                function (): array {
                    $records = [];
                    foreach ($this->firestore()->collection('landmarks')->documents() as $document) {
                        if ($document->exists()) {
                            $records[] = [
                                'id' => $document->id(),
                                'data' => $document->data(),
                            ];
                        }
                    }

                    return $records;
                }
            );
            $allLandmarks = array_map(
                fn (array $record) => new ArrayDocumentSnapshot((string) $record['id'], $record['data']),
                $records
            );
        }

        if ($sessionRole === 'site_manager' && $sessionUid !== '') {
            $sessionUidTrim = trim($sessionUid);
            $allLandmarks = array_values(array_filter($allLandmarks, function ($doc) use ($sessionUidTrim) {
                if (! $doc->exists()) {
                    return false;
                }
                $d = $doc->data();
                $m = trim((string) ($d['manager_uid'] ?? $d['managerUid'] ?? ''));

                return $m !== '' && $m === $sessionUidTrim;
            }));
        }

        $landmarkStatusFilter = 'all';
        $landmarkSearch = '';
        $landmarkCategoryFilter = 'all';
        $landmarkSort = null;
        $landmarkSortDirection = null;
        if ($sessionRole === 'admin') {
            $landmarkSearch = trim((string) $request->query('search', ''));
            $landmarkStatusFilter = strtolower((string) $request->query('status', 'all'));
            $landmarkCategoryFilter = strtolower(trim((string) $request->query('category', 'all')));
            $landmarkSort = strtolower(trim((string) $request->query('sort', '')));
            $landmarkSortDirection = strtolower(trim((string) $request->query('direction', 'asc')));
            $allowedSorts = ['name', 'location', 'category', 'status'];
            if (! in_array($landmarkStatusFilter, ['pending', 'active', 'rejected', 'all'], true)) {
                $landmarkStatusFilter = 'all';
            }
            if (! in_array($landmarkCategoryFilter, ['all', 'historical', 'religious', 'modern', 'natural', 'cultural', 'others'], true)) {
                $landmarkCategoryFilter = 'all';
            }
            if (! in_array($landmarkSort, $allowedSorts, true)) {
                $landmarkSort = null;
                $landmarkSortDirection = null;
            } elseif (! in_array($landmarkSortDirection, ['asc', 'desc'], true)) {
                $landmarkSortDirection = 'asc';
            }

            $searchNeedle = strtolower($landmarkSearch);
            $knownCategories = ['historical', 'religious', 'modern', 'natural', 'cultural'];
            $allLandmarks = array_values(array_filter($allLandmarks, function ($doc) use ($landmarkStatusFilter, $landmarkCategoryFilter, $searchNeedle, $knownCategories) {
                if (! $doc->exists()) {
                    return false;
                }

                $data = $doc->data();
                $activation = strtolower((string) ($data['activation_status'] ?? 'active'));
                if ($landmarkStatusFilter !== 'all' && $activation !== $landmarkStatusFilter) {
                    return false;
                }

                $category = strtolower(trim((string) ($data['category'] ?? '')));
                if ($landmarkCategoryFilter === 'others') {
                    if ($category !== '' && in_array($category, $knownCategories, true)) {
                        return false;
                    }
                } elseif ($landmarkCategoryFilter !== 'all' && $category !== $landmarkCategoryFilter) {
                    return false;
                }

                if ($searchNeedle !== '') {
                    $haystack = strtolower(implode(' ', [
                        trim((string) ($data['name'] ?? '')),
                        trim((string) ($data['location'] ?? '')),
                        trim((string) ($data['category'] ?? '')),
                    ]));

                    if (! str_contains($haystack, $searchNeedle)) {
                        return false;
                    }
                }

                return true;
            }));

            usort($allLandmarks, function ($left, $right) use ($landmarkStatusFilter, $landmarkSort, $landmarkSortDirection) {
                $defaultComparison = LandmarkApprovalOrder::compare(
                    $left->data(),
                    $left->id(),
                    $right->data(),
                    $right->id(),
                    $landmarkStatusFilter === 'all'
                );

                if ($landmarkSort === null) {
                    return $defaultComparison;
                }

                $sortValue = static function (array $data, string $sort): string {
                    if ($sort === 'status') {
                        return match (strtolower((string) ($data['activation_status'] ?? 'active'))) {
                            'pending' => 'Pending',
                            'rejected' => 'Rejected',
                            default => 'Approved',
                        };
                    }

                    return trim((string) ($data[$sort] ?? ''));
                };

                $comparison = strnatcasecmp(
                    $sortValue($left->data(), $landmarkSort),
                    $sortValue($right->data(), $landmarkSort)
                );

                if ($comparison === 0) {
                    return $defaultComparison;
                }

                return $landmarkSortDirection === 'desc' ? -$comparison : $comparison;
            });
        } elseif ($sessionRole === 'site_manager') {
            $landmarkSearch = trim((string) $request->query('search', ''));
            $landmarkCategoryFilter = strtolower(trim((string) $request->query('category', 'all')));
            $landmarkStatusFilter = strtolower(trim((string) $request->query('status', 'all')));
            $landmarkSort = strtolower(trim((string) $request->query('sort', '')));
            $landmarkSortDirection = strtolower(trim((string) $request->query('direction', 'asc')));
            $allowedCategories = ['all', 'historical', 'religious', 'natural', 'modern'];
            $allowedStatuses = ['all', 'active', 'pending', 'rejected'];
            $allowedSorts = ['name', 'location', 'category', 'status'];

            if (! in_array($landmarkCategoryFilter, $allowedCategories, true)) {
                $landmarkCategoryFilter = 'all';
            }
            if (! in_array($landmarkStatusFilter, $allowedStatuses, true)) {
                $landmarkStatusFilter = 'all';
            }
            if (! in_array($landmarkSort, $allowedSorts, true)) {
                $landmarkSort = null;
                $landmarkSortDirection = null;
            } elseif (! in_array($landmarkSortDirection, ['asc', 'desc'], true)) {
                $landmarkSortDirection = 'asc';
            }
            $searchNeedle = strtolower($landmarkSearch);
            $allLandmarks = array_values(array_filter($allLandmarks, function ($doc) use ($searchNeedle, $landmarkCategoryFilter, $landmarkStatusFilter) {
                if (! $doc->exists()) {
                    return false;
                }

                $data = $doc->data();
                if ($searchNeedle !== '') {
                    $haystack = strtolower(trim((string) ($data['name'] ?? '')).' '.trim((string) ($data['location'] ?? '')));
                    if (! str_contains($haystack, $searchNeedle)) {
                        return false;
                    }
                }

                if ($landmarkCategoryFilter !== 'all'
                    && strtolower(trim((string) ($data['category'] ?? ''))) !== $landmarkCategoryFilter) {
                    return false;
                }

                $status = strtolower(trim((string) ($data['activation_status'] ?? $data['status'] ?? 'active')));
                if ($landmarkStatusFilter !== 'all' && $status !== $landmarkStatusFilter) {
                    return false;
                }

                return true;
            }));

            if ($landmarkSort !== null) {
                usort($allLandmarks, function ($left, $right) use ($landmarkSort, $landmarkSortDirection) {
                    $sortValue = static function (array $data, string $sort): string {
                        if ($sort === 'status') {
                            return match (strtolower((string) ($data['activation_status'] ?? $data['status'] ?? 'active'))) {
                                'pending' => 'Pending',
                                'rejected' => 'Rejected',
                                default => 'Active',
                            };
                        }

                        return trim((string) ($data[$sort] ?? ''));
                    };

                    $comparison = strnatcasecmp(
                        $sortValue($left->data(), $landmarkSort),
                        $sortValue($right->data(), $landmarkSort)
                    );

                    if ($comparison !== 0) {
                        return $landmarkSortDirection === 'desc' ? -$comparison : $comparison;
                    }

                    return strcmp($left->id(), $right->id());
                });
            }

        }

        $openLandmarkId = trim((string) ($openLandmarkId ?? ''));
        $openViewModalId = null;
        if (in_array($sessionRole, ['site_manager', 'admin'], true) && $openLandmarkId !== '') {
            $openViewModalId = 'viewModal_'.preg_replace('/[^a-zA-Z0-9_-]/', '_', $openLandmarkId);

            $openInList = false;
            foreach ($allLandmarks as $doc) {
                if ($doc->exists() && $doc->id() === $openLandmarkId) {
                    $openInList = true;
                    break;
                }
            }
            if (! $openInList) {
                $openSnap = $this->firestore()->collection('landmarks')->document($openLandmarkId)->snapshot();
                if ($openSnap->exists() && $this->actorMayViewLandmark($request, $openSnap->data())) {
                    array_unshift($allLandmarks, $openSnap);
                }
            }
        }

        $paginationPath = $sessionRole === 'site_manager'
            ? route('sitemanager.landmarks')
            : route('admin.landmarks');

        $page = max(1, (int) $request->get('page', 1));
        if ($openLandmarkId !== '' && in_array($sessionRole, ['site_manager', 'admin'], true)) {
            foreach ($allLandmarks as $index => $doc) {
                if ($doc->exists() && $doc->id() === $openLandmarkId) {
                    $page = (int) floor($index / $perPage) + 1;
                    break;
                }
            }
        }
        $items = array_slice($allLandmarks, ($page - 1) * $perPage, $perPage);

        $landmarks = new LengthAwarePaginator(
            $items,
            count($allLandmarks),
            $perPage,
            $page,
            ['path' => $paginationPath, 'query' => $request->except(['view', 'page', 'order'])]
        );

        return view('admin.landmarks', [
            'landmarks' => $landmarks,
            'landmarkStatusFilter' => $landmarkStatusFilter,
            'landmarkSearch' => $landmarkSearch ?? '',
            'landmarkCategoryFilter' => $landmarkCategoryFilter ?? 'all',
            'landmarkSort' => $landmarkSort,
            'landmarkSortDirection' => $landmarkSortDirection,
            'isLandmarkApprovalQueue' => $sessionRole === 'admin',
            'openViewModalId' => $openViewModalId,
            'openLandmarkId' => $openLandmarkId !== '' ? $openLandmarkId : null,
        ]);
    }

    public function showLandmark(Request $request, string $id)
    {
        $snap = $this->firestore()->collection('landmarks')->document($id)->snapshot();
        if (! $snap->exists()) {
            abort(404);
        }

        $data = $snap->data();
        if (! $this->actorMayViewLandmark($request, $data)) {
            abort(403);
        }

        if (in_array($request->session()->get('role'), ['site_manager', 'admin'], true)) {
            if ($request->query->has('view')) {
                $routeName = $request->session()->get('role') === 'site_manager'
                    ? 'sitemanager.landmarks.show'
                    : 'admin.landmarks.show';

                return redirect()->route($routeName, array_merge(['id' => $id], $request->except('view')));
            }

            return $this->landmarksIndexView($request, $id);
        }

        abort(403);
    }

    public function approveLandmark(Request $request, string $id)
    {
        if ($request->session()->get('role') !== 'admin') {
            abort(403);
        }

        $landmarkRef = $this->firestore()->collection('landmarks')->document($id);
        $snap = $landmarkRef->snapshot();
        if (! $snap->exists()) {
            return redirect()->route('admin.landmarks')
                ->with('status_err', 'Landmark not found.');
        }

        $data = $snap->data();
        $status = strtolower((string) ($data['activation_status'] ?? ''));
        if ($status !== 'pending') {
            return redirect()->route('admin.landmarks', ['status' => 'pending'])
                ->with('status_err', 'Only landmarks pending approval can be approved.');
        }

        $evidence = $data['evidence_documents'] ?? [];
        if (! is_array($evidence) || $evidence === []) {
            return redirect()->route('admin.landmarks', ['status' => 'pending', 'open' => $id])
                ->with('status_err', 'This landmark has no evidence on file. Reject the submission instead.');
        }

        $now = now()->toDateTimeString();
        $actorUid = (string) $request->session()->get('uid', '');

        try {
            $landmarkRef->set([
                'activation_status' => 'active',
                'activated_at' => $now,
                'approved_by_uid' => $actorUid !== '' ? $actorUid : null,
                'updated_at' => $now,
            ], ['merge' => true]);

        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.landmarks', ['status' => 'pending', 'open' => $id])
                ->with('status_err', 'Could not approve landmark: '.$e->getMessage());
        }
        $this->siteManagerReadModel->forget((string) ($data['manager_uid'] ?? $data['managerUid'] ?? ''));
        Cache::forget(self::LANDMARK_APPROVAL_CACHE_KEY);

        return redirect()->route('admin.landmarks', ['status' => 'active'])
            ->with('status', 'Landmark approved and published.');
    }

    public function rejectLandmark(Request $request, string $id)
    {
        if ($request->session()->get('role') !== 'admin') {
            abort(403);
        }

        $landmarkRef = $this->firestore()->collection('landmarks')->document($id);
        $snap = $landmarkRef->snapshot();
        if (! $snap->exists()) {
            return redirect()->route('admin.landmarks')
                ->with('status_err', 'Landmark not found.');
        }

        $data = $snap->data();
        $status = strtolower((string) ($data['activation_status'] ?? ''));
        if ($status !== 'pending') {
            return redirect()->route('admin.landmarks', ['status' => 'pending'])
                ->with('status_err', 'Only landmarks pending approval can be rejected.');
        }

        $now = now()->toDateTimeString();
        $actorUid = (string) $request->session()->get('uid', '');

        try {
            $landmarkRef->set([
                'activation_status' => 'rejected',
                'rejected_at' => $now,
                'rejected_by_uid' => $actorUid !== '' ? $actorUid : null,
                'updated_at' => $now,
            ], ['merge' => true]);

        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.landmarks', ['status' => 'pending', 'open' => $id])
                ->with('status_err', 'Could not reject landmark: '.$e->getMessage());
        }
        $this->siteManagerReadModel->forget((string) ($data['manager_uid'] ?? $data['managerUid'] ?? ''));
        Cache::forget(self::LANDMARK_APPROVAL_CACHE_KEY);

        return redirect()->route('admin.landmarks', ['status' => 'rejected'])
            ->with('status', 'Landmark submission rejected.');
    }

    /**
     * Landmark document IDs this LM may see curators for: same portfolio as {@see CuratorAccessibleLandmarks}
     * (all sites sharing the same non-empty manager_uid on the landmark record).
     *
     * @return list<string>
     */
    private function landmarkIdsManagedBy(string $managerUid): array
    {
        $managerUid = trim($managerUid);
        if ($managerUid === '') {
            return [];
        }

        $seedLandmarkId = null;
        foreach ($this->firestore()->collection('landmarks')->documents() as $doc) {
            if (! $doc->exists()) {
                continue;
            }
            $d = $doc->data();
            $docManager = trim((string) ($d['manager_uid'] ?? $d['managerUid'] ?? ''));
            if ($docManager !== '' && $docManager === $managerUid) {
                $seedLandmarkId = $doc->id();
                break;
            }
        }

        if ($seedLandmarkId === null) {
            return [];
        }

        return CuratorAccessibleLandmarks::resolveIds($this->firebase, $seedLandmarkId);
    }

    private function actorMayApprovePendingUser(string $actorRole, string $actorUid, array $data): bool
    {
        $targetRole = strtolower((string) ($data['role'] ?? ''));

        if ($actorRole === 'admin') {
            return UserApprovalPolicy::superAdminApprovesRole($targetRole);
        }

        if ($actorRole !== 'site_manager' || $actorUid === '') {
            return false;
        }

        if (! UserApprovalPolicy::siteManagerApprovesRole($targetRole)) {
            return false;
        }

        $role = $targetRole;
        if ($role !== 'curator') {
            return false;
        }

        if (($data['curator_registration_type'] ?? '') !== 'existing_landmark') {
            return false;
        }

        $landmarkId = (string) ($data['assigned_landmark_id'] ?? '');
        if ($landmarkId === '') {
            return false;
        }

        $snap = $this->firestore()->collection('landmarks')->document($landmarkId)->snapshot();
        if (! $snap->exists()) {
            return false;
        }

        $managerUid = (string) ($snap->data()['manager_uid'] ?? '');

        return $managerUid !== '' && $managerUid === $actorUid;
    }

    /** @param  array<string, mixed>  $landmarkData */
    private function actorMayViewLandmark(Request $request, array $landmarkData): bool
    {
        $role = (string) $request->session()->get('role', '');
        if ($role === 'admin') {
            return true;
        }

        if ($role !== 'site_manager') {
            return false;
        }

        $actorUid = trim((string) $request->session()->get('uid', ''));
        $managerUid = trim((string) ($landmarkData['manager_uid'] ?? $landmarkData['managerUid'] ?? ''));

        return $actorUid !== '' && $managerUid !== '' && $actorUid === $managerUid;
    }
}
