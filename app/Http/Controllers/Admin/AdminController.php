<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Kreait\Firebase\Factory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AdminController extends Controller
{
    protected $auth;
    protected $firestore;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->auth = $firebaseService->auth();
        $this->firestore = $firebaseService->firestore();
    }

    public function dashboard()
    {
        $firebase = (new Factory)
            ->withServiceAccount(storage_path('app/firebase_credentials.json'))
            ->createFirestore();

        $firestore = $firebase->database();

        $usersCollection = $firestore->collection('users');
        $allUsers = $usersCollection->documents();
        $users = iterator_to_array($allUsers->rows());
        $userCount = count($users);

        $curatorCount = count(array_filter($users, fn($user) => $user->data()['role'] === 'curator'));
        $adminCount = count(array_filter($users, fn($user) => $user->data()['role'] === 'admin'));

        $landmarkCount = $firestore->collection('landmarks')->documents()->size();

        $logsSnapshot = $firestore->collection('logs')->documents();
        $logs = iterator_to_array($logsSnapshot->rows());
        $logCount = count($logs);

        $visitsByDay = [
            'Sun' => 0, 'Mon' => 0, 'Tue' => 0, 'Wed' => 0,
            'Thu' => 0, 'Fri' => 0, 'Sat' => 0,
        ];

        foreach ($logs as $log) {
            $timestamp = $log->data()['timestamp'] ?? null;

            if ($timestamp) {
                try {
                    $day = Carbon::parse($timestamp)->format('D'); 
                    if (isset($visitsByDay[$day])) {
                        $visitsByDay[$day]++;
                    }
                } catch (\Exception $e) {
                    // Ignore bad timestamps
                }
            }
        }

        return view('admin.dashboard', [
            'userCount' => $userCount,
            'curatorCount' => $curatorCount,
            'adminCount' => $adminCount,
            'landmarkCount' => $landmarkCount,
            'logCount' => $logCount,
            'visitsByDay' => array_values($visitsByDay),
        ]);
    }

    public function users(\Illuminate\Http\Request $request)
{
    $search = strtolower($request->input('search'));
    $roleFilter = strtolower($request->input('role'));

    // 1. Get Firebase Auth users
    $authUsers = iterator_to_array($this->auth->listUsers());

    // 2. Get Firestore user roles
    $usersCollection = $this->firestore->collection('users')->documents();
    $firestoreRoles = [];
    foreach ($usersCollection as $doc) {
        $data = $doc->data();
        if (isset($data['role'])) {
            $firestoreRoles[$doc->id()] = strtolower($data['role']); // uid => role
        }
    }

    // 3. Merge Auth + Firestore roles
    $mergedUsers = [];
    foreach ($authUsers as $user) {
        $role = strtolower($user->customClaims['role'] ?? '');
        $uid  = $user->uid;

        // Prefer Firestore role if available
        if (isset($firestoreRoles[$uid])) {
            $role = $firestoreRoles[$uid];
        }

        // Default if none
        if (!$role) {
            $role = 'visitor';
        }

        // Apply filters
        $email = strtolower($user->email ?? '');
        $matchesSearch = !$search || str_contains($email, $search) || str_contains($uid, $search) || str_contains($role, $search);
        $matchesRole   = !$roleFilter || $role === $roleFilter;

        if ($matchesSearch && $matchesRole) {
            $mergedUsers[] = (object) [
                'email' => $user->email,
                'uid'   => $uid,
                'role'  => $role,
            ];
        }
    }

    return view('admin.users', [
        'users' => $mergedUsers,
        'search' => $search,
        'roleFilter' => $roleFilter,
    ]);
}

    public function curators()
    {
        $users = $this->auth->listUsers();
        $curators = [];

        foreach ($users as $user) {
            if (isset($user->customClaims['role']) && $user->customClaims['role'] === 'curator') {
                $curators[] = $user;
            }
        }

        return view('admin.curators', compact('curators'));
    }

    public function landmarks(\Illuminate\Http\Request $request)
{
    $perPage = 4; // Only paginate in Card View
    $landmarksQuery = $this->firestore->collection('landmarks')->documents();

    // Convert snapshot into array
    $allLandmarks = iterator_to_array($landmarksQuery);

    // If "list" view requested → return ALL
    if ($request->get('view') === 'list') {
        $landmarks = collect($allLandmarks); // no pagination
    } else {
        // Default = Card View → paginate manually
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

        return redirect()->route('admin.logs')->with('status', '✅ All logs have been cleared.');
    }

}
