<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CuratorAccessibleLandmarks;
use App\Services\FirebaseService;
use App\Services\SiteManagerLandmarks;
use App\Support\FirestoreBool;
use App\Support\UserApprovalPolicy;
use Carbon\Carbon;
use Google\Cloud\Firestore\FieldValue;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminController extends Controller
{
    protected $auth;

    protected $firestore;

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

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebase = $firebaseService;
        $this->auth = $firebaseService->auth();
        $this->firestore = $firebaseService->firestore();
    }

    public function dashboard()
    {
        $isSystemAdmin = request()->session()->get('role') === 'admin';
        $sessionUid = (string) request()->session()->get('uid', '');
        $firestore = $this->firestore;

        $usersCollection = $firestore->collection('users');
        $allUsers = $usersCollection->documents();
        $users = iterator_to_array($allUsers->rows());

        $userCount = 0;
        $curatorCount = 0;
        $adminCount = 0;

        foreach ($users as $user) {
            if (! $user->exists()) {
                continue;
            }
            $role = strtolower((string) ($user->data()['role'] ?? ''));
            $userCount++;
            if ($role === 'curator') {
                $curatorCount++;
            }
            if ($role === 'admin') {
                $adminCount++;
            }
        }

        $landmarkCount = 0;
        foreach ($firestore->collection('landmarks')->documents() as $doc) {
            if ($doc->exists()) {
                $landmarkCount++;
            }
        }

        if (! $isSystemAdmin && $sessionUid !== '') {
            $managedLandmarkIds = $this->landmarkIdsManagedBy($sessionUid);
            $managedSet = array_flip($managedLandmarkIds);
            $landmarkCount = count($managedLandmarkIds);

            $curatorCount = count(array_filter($users, function ($user) use ($managedSet) {
                if (! $user->exists()) {
                    return false;
                }
                $d = $user->data();
                if (($d['role'] ?? '') !== 'curator') {
                    return false;
                }
                $lid = isset($d['assigned_landmark_id']) ? (string) $d['assigned_landmark_id'] : '';

                return $lid !== '' && isset($managedSet[$lid]);
            }));
        }

        $logCount = 0;
        $visitsByDay = [
            'Sun' => 0, 'Mon' => 0, 'Tue' => 0, 'Wed' => 0,
            'Thu' => 0, 'Fri' => 0, 'Sat' => 0,
        ];
        $visitsByDayValues = [];

        if ($isSystemAdmin) {
            $logsSnapshot = $firestore->collection('logs')->documents();
            $logs = iterator_to_array($logsSnapshot->rows());
            $logCount = count($logs);

            foreach ($logs as $log) {
                if (! $log->exists()) {
                    continue;
                }
                $timestamp = $log->data()['timestamp'] ?? null;
                if (! $timestamp) {
                    continue;
                }
                try {
                    $day = Carbon::parse($timestamp)->format('D');
                    if (isset($visitsByDay[$day])) {
                        $visitsByDay[$day]++;
                    }
                } catch (\Exception $e) {
                    //
                }
            }

            $visitsByDayValues = array_values($visitsByDay);
        }

        return view('admin.dashboard', [
            'userCount' => $userCount,
            'curatorCount' => $curatorCount,
            'adminCount' => $adminCount,
            'landmarkCount' => $landmarkCount,
            'logCount' => $logCount,
            'visitsByDay' => $visitsByDayValues,
            'showSystemInsights' => $isSystemAdmin,
        ]);
    }

    public function users(Request $request)
    {
        $search = strtolower(trim((string) $request->input('search', '')));
        $roleFilter = strtolower(trim((string) $request->input('role', '')));
        $curatorsOnly = $request->routeIs('sitemanager.curators');
        $siteManagersOnly = $request->routeIs('admin.site-managers');
        if ($curatorsOnly) {
            $roleFilter = 'curator';
        }
        if ($siteManagersOnly) {
            $roleFilter = 'site_manager';
        }

        $sessionRole = (string) $request->session()->get('role', '');
        $sessionUid = (string) $request->session()->get('uid', '');

        $managedLandmarkSet = [];
        if ($curatorsOnly && $sessionRole === 'site_manager' && $sessionUid !== '') {
            foreach ($this->landmarkIdsManagedBy($sessionUid) as $mid) {
                $k = trim((string) $mid);
                if ($k !== '') {
                    $managedLandmarkSet[$k] = true;
                }
            }
        }

        $authUsers = iterator_to_array($this->auth->listUsers());

        $usersCollection = $this->firestore->collection('users')->documents();
        $firestoreProfiles = [];

        foreach ($usersCollection as $doc) {
            if ($doc->exists()) {
                $firestoreProfiles[$doc->id()] = $doc->data();
            }
        }

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

            if ($siteManagersOnly && $role !== 'site_manager') {
                continue;
            }

            $visitorPatch = UserApprovalPolicy::visitorAutoApprovalPatch(array_merge($profile, ['role' => $role]));
            if ($visitorPatch !== null) {
                $this->firestore->collection('users')->document($uid)->set($visitorPatch, ['merge' => true]);
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
                $effectiveProfile = array_merge($profile, [
                    'role' => $role,
                    'assigned_landmark_id' => $assignedLandmarkId,
                    'curator_registration_type' => $curatorRegistrationType,
                ]);

                $approvalActions = $requiresApproval && $approvalStatus === 'pending'
                    && $this->actorMayApprovePendingUser($sessionRole, $sessionUid, $effectiveProfile);

                $mergedUsers[] = (object) [
                    'email' => $user->email,
                    'uid' => $uid,
                    'role' => $role,
                    'requires_approval' => $requiresApproval,
                    'approval_status' => $approvalStatus,
                    'curator_registration_type' => $curatorRegistrationType,
                    'assigned_landmark_id' => $assignedLandmarkId,
                    'approval_actions' => $approvalActions,
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
        $allActiveLandmarksAssigned = false;
        if ($curatorsOnly && $sessionRole === 'site_manager' && $sessionUid !== '') {
            $assignmentLandmarks = app(SiteManagerLandmarks::class)->curatorAssignmentLandmarks($sessionUid);
            $assignableLandmarks = $assignmentLandmarks['assignable'];
            $allActiveLandmarksAssigned = $assignmentLandmarks['all_active_assigned'];
        }

        return view('admin.users', [
            'users' => $mergedUsers,
            'search' => $search,
            'roleFilter' => $roleFilter,
            'curatorsOnly' => $curatorsOnly,
            'siteManagersOnly' => $siteManagersOnly,
            'assignableLandmarks' => $assignableLandmarks,
            'allActiveLandmarksAssigned' => $allActiveLandmarksAssigned,
        ]);
    }

    public function approveUser(Request $request, string $uid)
    {
        $snapshot = $this->firestore->collection('users')->document($uid)->snapshot();

        if (! $snapshot->exists()) {
            return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
                ->with('status_err', 'User profile not found in database.');
        }

        $data = $snapshot->data();
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

                $landmarkRef = $this->firestore->collection('landmarks')->document($pendingProposalLandmarkId);
                $lmSnap = $landmarkRef->snapshot();
                if (! $lmSnap->exists()) {
                    throw new \RuntimeException('The proposed landmark no longer exists. Reject this signup instead.');
                }

                $landmarkRef->set([
                    'activation_status' => 'active',
                    'activated_at' => $now,
                ], ['merge' => true]);
            }

            $docRef = $this->firestore->collection('users')->document($uid);
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

            $approvedByLabel = $actorRole === 'site_manager' ? 'Site Manager' : 'Admin';

            $this->firestore->collection('logs')->add([
                'email' => $data['email'] ?? '',
                'action' => 'User approved by '.$approvedByLabel,
                'uid' => $uid,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
                ->with('status_err', 'Approve failed: '.$e->getMessage());
        }

        return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
            ->with('status', 'User approved successfully.');
    }

    public function rejectUser(Request $request, string $uid)
    {
        $snapshot = $this->firestore->collection('users')->document($uid)->snapshot();

        if (! $snapshot->exists()) {
            return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
                ->with('status_err', 'User profile not found in database.');
        }

        $data = $snapshot->data();
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

        $email = $data['email'] ?? '';

        try {
            if (($data['role'] ?? '') === 'curator' && ! empty($data['pending_landmark_id'])) {
                $pendingProposalLandmarkId = (string) $data['pending_landmark_id'];
                if ($pendingProposalLandmarkId !== '') {
                    try {
                        $this->firestore->collection('landmarks')->document($pendingProposalLandmarkId)->delete();
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }

            $rejectedByLabel = $actorRole === 'site_manager' ? 'Site Manager' : 'Admin';

            $this->firestore->collection('logs')->add([
                'email' => $email,
                'action' => 'User rejected by '.$rejectedByLabel.' (Firebase Auth deleted)',
                'uid' => $uid,
                'timestamp' => now()->toISOString(),
            ]);

            $this->auth->deleteUser($uid);
            $this->firestore->collection('users')->document($uid)->delete();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
                ->with('status_err', 'Could not reject user in Firebase: '.$e->getMessage());
        }

        return redirect()->route($this->usersIndexRouteName(), $request->only(['search', 'role']))
            ->with('status', 'Registration rejected. Firebase account and profile were removed.');
    }

    public function landmarks(Request $request)
    {
        $open = trim((string) $request->query('open', ''));
        $sessionRole = (string) $request->session()->get('role', '');
        if ($open !== '' && $sessionRole === 'site_manager') {
            return redirect()->route('sitemanager.landmarks.show', array_filter([
                'id' => $open,
                'view' => $request->query('view'),
            ]));
        }
        if ($open !== '' && $sessionRole === 'admin') {
            return redirect()->route('admin.landmarks.show', array_filter([
                'id' => $open,
                'view' => $request->query('view'),
                'status' => $request->query('status'),
            ]));
        }

        return $this->landmarksIndexView($request);
    }

    /**
     * @return \Illuminate\View\View
     */
    private function landmarksIndexView(Request $request, ?string $openLandmarkId = null)
    {
        $perPage = 4;
        $sessionRole = (string) $request->session()->get('role', '');
        $sessionUid = (string) $request->session()->get('uid', '');

        $landmarksQuery = $this->firestore->collection('landmarks')->documents();
        $allLandmarks = iterator_to_array($landmarksQuery);

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
        if ($sessionRole === 'admin') {
            $landmarkStatusFilter = strtolower((string) $request->query('status', 'pending'));
            if (! in_array($landmarkStatusFilter, ['pending', 'active', 'rejected', 'all'], true)) {
                $landmarkStatusFilter = 'pending';
            }
            if ($landmarkStatusFilter !== 'all') {
                $allLandmarks = array_values(array_filter($allLandmarks, function ($doc) use ($landmarkStatusFilter) {
                    if (! $doc->exists()) {
                        return false;
                    }
                    $activation = strtolower((string) ($doc->data()['activation_status'] ?? 'active'));

                    return $activation === $landmarkStatusFilter;
                }));
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
                $openSnap = $this->firestore->collection('landmarks')->document($openLandmarkId)->snapshot();
                if ($openSnap->exists() && $this->actorMayViewLandmark($request, $openSnap->data())) {
                    array_unshift($allLandmarks, $openSnap);
                }
            }
        }

        $paginationPath = $sessionRole === 'site_manager'
            ? route('sitemanager.landmarks')
            : route('admin.landmarks');

        if ($request->get('view') === 'list') {
            $landmarks = collect($allLandmarks);
        } else {
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
                ['path' => $paginationPath, 'query' => $request->query()]
            );
        }

        return view('admin.landmarks', [
            'landmarks' => $landmarks,
            'landmarkStatusFilter' => $landmarkStatusFilter,
            'isLandmarkApprovalQueue' => $sessionRole === 'admin',
            'openViewModalId' => $openViewModalId,
            'openLandmarkId' => $openLandmarkId !== '' ? $openLandmarkId : null,
        ]);
    }

    public function showLandmark(Request $request, string $id)
    {
        $snap = $this->firestore->collection('landmarks')->document($id)->snapshot();
        if (! $snap->exists()) {
            abort(404);
        }

        $data = $snap->data();
        if (! $this->actorMayViewLandmark($request, $data)) {
            abort(403);
        }

        if (in_array($request->session()->get('role'), ['site_manager', 'admin'], true)) {
            return $this->landmarksIndexView($request, $id);
        }

        abort(403);
    }

    public function approveLandmark(Request $request, string $id)
    {
        if ($request->session()->get('role') !== 'admin') {
            abort(403);
        }

        $landmarkRef = $this->firestore->collection('landmarks')->document($id);
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

            $this->firestore->collection('logs')->add([
                'email' => (string) $request->session()->get('email', ''),
                'action' => 'Landmark approved by admin',
                'landmark_id' => $id,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.landmarks', ['status' => 'pending', 'open' => $id])
                ->with('status_err', 'Could not approve landmark: '.$e->getMessage());
        }

        return redirect()->route('admin.landmarks', ['status' => 'active'])
            ->with('status', 'Landmark approved and published.');
    }

    public function rejectLandmark(Request $request, string $id)
    {
        if ($request->session()->get('role') !== 'admin') {
            abort(403);
        }

        $landmarkRef = $this->firestore->collection('landmarks')->document($id);
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

            $this->firestore->collection('logs')->add([
                'email' => (string) $request->session()->get('email', ''),
                'action' => 'Landmark rejected by admin',
                'landmark_id' => $id,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.landmarks', ['status' => 'pending', 'open' => $id])
                ->with('status_err', 'Could not reject landmark: '.$e->getMessage());
        }

        return redirect()->route('admin.landmarks', ['status' => 'rejected'])
            ->with('status', 'Landmark submission rejected.');
    }

    public function logs()
    {
        $logsSnapshot = $this->firestore->collection('logs')->documents();
        $logs = iterator_to_array($logsSnapshot->rows());

        $usersSnapshot = $this->firestore->collection('users')->documents();
        $userRoles = [];

        foreach ($usersSnapshot as $userDoc) {
            $data = $userDoc->data();
            if (isset($data['email'], $data['role'])) {
                $userRoles[$data['email']] = $data['role'];
            }
        }

        return view('admin.logs', compact('logs', 'userRoles'));
    }

    public function clearLogs()
    {
        $logsCollection = $this->firestore->collection('logs');
        $documents = $logsCollection->documents();

        foreach ($documents as $doc) {
            $doc->reference()->delete();
        }

        return redirect()->route('admin.logs')->with('status', ' All logs have been cleared.');
    }

    /**
     * @return list<string>
     */
    /**
     * Landmark document IDs this LM may see curators for: same portfolio as {@see CuratorAccessibleLandmarks}
     * (all sites sharing the same non-empty manager_uid on the landmark record).
     */
    private function landmarkIdsManagedBy(string $managerUid): array
    {
        $managerUid = trim($managerUid);
        if ($managerUid === '') {
            return [];
        }

        $seedLandmarkId = null;
        foreach ($this->firestore->collection('landmarks')->documents() as $doc) {
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

        $snap = $this->firestore->collection('landmarks')->document($landmarkId)->snapshot();
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

    private function countPendingSiteManagerRegistrations(): int
    {
        $count = 0;
        foreach ($this->firestore->collection('users')->documents() as $doc) {
            if (! $doc->exists()) {
                continue;
            }
            $data = $doc->data();
            if (strtolower((string) ($data['role'] ?? '')) !== 'site_manager') {
                continue;
            }
            if (! FirestoreBool::isTrue($data['requires_approval'] ?? null)) {
                continue;
            }
            if (strtolower((string) ($data['approval_status'] ?? '')) === 'pending') {
                $count++;
            }
        }

        return $count;
    }

    private function countPendingLandmarkSubmissions(): int
    {
        $count = 0;
        foreach ($this->firestore->collection('landmarks')->documents() as $doc) {
            if (! $doc->exists()) {
                continue;
            }
            if (strtolower((string) ($doc->data()['activation_status'] ?? 'active')) === 'pending') {
                $count++;
            }
        }

        return $count;
    }
}

