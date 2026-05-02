<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CuratorAccessibleLandmarks;
use App\Services\FirebaseService;
use App\Services\LandmarkJoinQrService;
use App\Support\FirestoreBool;
use Google\Cloud\Firestore\FieldValue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminController extends Controller
{
    protected $auth;

    protected $firestore;

    protected FirebaseService $firebase;

    /** Route name for the users listing in the panel the current session uses. */
    protected function usersIndexRouteName(): string
    {
        return request()->session()->get('role') === 'admin' ? 'admin.users' : 'landmarkmanager.curators';
    }

    public function __construct(
        FirebaseService $firebaseService,
        protected LandmarkJoinQrService $joinQrService
    ) {
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
        $userCount = count($users);

        $curatorCount = count(array_filter($users, fn($user) => $user->data()['role'] === 'curator'));
        $adminCount = count(array_filter($users, fn($user) => $user->data()['role'] === 'admin'));

        $landmarkCount = $firestore->collection('landmarks')->documents()->size();

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
        $visitsByDayValues = [];

        $visitsByDay = [
            'Sun' => 0, 'Mon' => 0, 'Tue' => 0, 'Wed' => 0,
            'Thu' => 0, 'Fri' => 0, 'Sat' => 0,
        ];

        if ($isSystemAdmin) {
            $logsSnapshot = $firestore->collection('logs')->documents();
            $logs = iterator_to_array($logsSnapshot->rows());
            $logCount = count($logs);

            foreach ($logs as $log) {
                $timestamp = $log->data()['timestamp'] ?? null;

                if ($timestamp) {
                    try {
                        $day = Carbon::parse($timestamp)->format('D');
                        if (isset($visitsByDay[$day])) {
                            $visitsByDay[$day]++;
                        }
                    } catch (\Exception $e) {
                        //
                    }
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
        $curatorsOnly = $request->routeIs('landmarkmanager.curators');
        if ($curatorsOnly) {
            $roleFilter = 'curator';
        }

        $sessionRole = (string) $request->session()->get('role', '');
        $sessionUid = (string) $request->session()->get('uid', '');

        $managedLandmarkSet = [];
        if ($curatorsOnly && $sessionRole === 'landmark_manager' && $sessionUid !== '') {
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

            $requiresApproval = FirestoreBool::isTrue($profile['requires_approval'] ?? null);
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

        if ($curatorsOnly && $sessionRole === 'landmark_manager') {
            $mergedUsers = array_values(array_filter($mergedUsers, function ($u) use ($managedLandmarkSet) {
                if ($u->role !== 'curator') {
                    return false;
                }
                $lidKey = trim((string) ($u->assigned_landmark_id ?? ''));

                return $lidKey !== '' && isset($managedLandmarkSet[$lidKey]);
            }));
        }

        return view('admin.users', [
            'users' => $mergedUsers,
            'search' => $search,
            'roleFilter' => $roleFilter,
            'curatorsOnly' => $curatorsOnly,
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

                $this->joinQrService->ensureJoinQrForLandmark($pendingProposalLandmarkId);
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

            $approvedByLabel = $actorRole === 'landmark_manager' ? 'Landmark Manager' : 'admin';

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

            $rejectedByLabel = $actorRole === 'landmark_manager' ? 'Landmark Manager' : 'admin';

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

    public function landmarks(\Illuminate\Http\Request $request)
    {
        $perPage = 4;
        $sessionRole = (string) $request->session()->get('role', '');
        $sessionUid = (string) $request->session()->get('uid', '');

        $landmarksQuery = $this->firestore->collection('landmarks')->documents();
        $allLandmarks = iterator_to_array($landmarksQuery);

        if ($sessionRole === 'landmark_manager' && $sessionUid !== '') {
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

        if ($request->get('view') === 'list') {
            $landmarks = collect($allLandmarks);
        } else {
            $page = $request->get('page', 1);
            $items = array_slice($allLandmarks, ($page - 1) * $perPage, $perPage);

            $landmarks = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                count($allLandmarks),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return view('admin.landmarks', compact('landmarks'));
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
        if ($actorRole === 'admin') {
            return true;
        }

        if ($actorRole !== 'landmark_manager' || $actorUid === '') {
            return false;
        }

        $role = strtolower((string) ($data['role'] ?? ''));
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
}

