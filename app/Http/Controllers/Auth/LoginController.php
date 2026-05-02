<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\FirestoreBool;
use Illuminate\Http\Request;
use App\Services\ActiveLandmarksCatalog;
use App\Services\CuratorAccessibleLandmarks;
use App\Services\FirebaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Kreait\Firebase\Exception\Auth\InvalidPassword;
use Kreait\Firebase\Exception\Auth\UserNotFound;

class LoginController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function showLoginForm()
    {
        $requestedRedirect = request()->query('redirect');
        if ($requestedRedirect) {
            $this->storeCuratorRedirectIfValid($requestedRedirect);
        }

        if (Session::has('uid') && Session::has('role')) {
            $role = Session::get('role');

            if ($role === 'curator') {
                $aid = Session::get('assigned_landmark_id');
                if (! is_string($aid) || trim($aid) === '') {
                    return redirect()->route('curators.pending-assignment');
                }
                $redirectTo = $this->pullCuratorRedirectOrDefault(false);
                return redirect()->to($redirectTo);
            }

            if ($role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            if ($role === 'landmark_manager') {
                return redirect()->route('landmarkmanager.dashboard');
            }
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $email = $request->email;
        $password = $request->password;

        try {
            $signInResult = $this->firebase->getAuth()->signInWithEmailAndPassword($email, $password);
            $idToken = $signInResult->idToken();
            $firebaseUser = $this->firebase->getAuth()->verifyIdToken($idToken);
            $uid = $firebaseUser->claims()->get('sub');
            $role = $firebaseUser->claims()->get('role');

            
            $userDoc = $this->firebase->firestore()->collection('users')->document($uid)->snapshot();
            $name = $userDoc->exists() ? ($userDoc['name'] ?? null) : null;
            $approvalStatus = $userDoc->exists()
                ? strtolower((string) ($userDoc['approval_status'] ?? 'approved'))
                : 'approved';
            $requiresApproval = $userDoc->exists()
                ? FirestoreBool::isTrue($userDoc['requires_approval'] ?? null)
                : false;
            $fsRole = strtolower((string) ($userDoc->exists() ? ($userDoc['role'] ?? $role) : $role));

            if ($requiresApproval && $approvalStatus === 'rejected') {
                return back()->withErrors([
                    'error' => 'Your registration was not approved by the administrator. Please contact support if you have questions.',
                ]);
            }

            if ($requiresApproval && $approvalStatus !== 'approved') {
                if ($fsRole === 'landmark_manager') {
                    $pendingMsg = 'Your account is waiting for administrator approval.';
                } elseif ($fsRole === 'curator') {
                    $pendingMsg = 'Your account is waiting for approval from your Landmark Manager.';
                } else {
                    $pendingMsg = 'Your account is pending approval before activation.';
                }

                return back()->withErrors(['error' => $pendingMsg]);
            }


            
            Session::put('uid', $uid);
            Session::put('role', $role);
            Session::put('email', $email);
            if ($name) {
                Session::put('name', $name);
            }

            if ($fsRole === 'curator') {
                $assignedLandmarkIdRaw = $userDoc->exists() ? ($userDoc['assigned_landmark_id'] ?? '') : '';
                $assignedTrimmed = is_string($assignedLandmarkIdRaw) ? trim($assignedLandmarkIdRaw) : '';
                Session::put('assigned_landmark_id', $assignedTrimmed);
                Session::put('browseable_landmark_ids', ActiveLandmarksCatalog::documentIds($this->firebase));
                Session::put(
                    'writable_landmark_ids',
                    $assignedTrimmed !== ''
                        ? CuratorAccessibleLandmarks::resolveIds($this->firebase, $assignedTrimmed)
                        : []
                );
            } else {
                Session::forget('assigned_landmark_id');
                Session::forget('browseable_landmark_ids');
                Session::forget('writable_landmark_ids');
            }

            
            $this->firebase->firestore()->collection('logs')->add([
                'email' => $email,
                'action' => 'Logged in',
                'timestamp' => now()->toISOString(),
            ]);

            
            if ($role === 'admin') {
                return redirect()->route('admin.dashboard')->with('success', 'Welcome Admin!');
            } elseif ($role === 'curator') {
                $assignedAfterLogin = Session::get('assigned_landmark_id');
                $redirectTo =
                    (! is_string($assignedAfterLogin) || trim($assignedAfterLogin) === '')
                        ? route('curators.pending-assignment')
                        : $this->pullCuratorRedirectOrDefault();
                return redirect()->to($redirectTo)->with('success', 'Welcome Curator!');
            } elseif ($role === 'landmark_manager') {
                return redirect()->route('landmarkmanager.dashboard')->with('success', 'Welcome Landmark Manager!');
            } else {
                return back()->withErrors(['error' => 'Unauthorized role.']);
            }

        } catch (UserNotFound | InvalidPassword $e) {
            return back()->withErrors(['error' => 'Invalid email or password.']);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Login failed. ' . $e->getMessage()]);
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        $email = $request->session()->get('email');

        if ($email) {
            $this->firebase->firestore()->collection('logs')->add([
                'email' => $email,
                'action' => 'Logged out',
                'timestamp' => now()->toISOString(),
            ]);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }

    private function storeCuratorRedirectIfValid(string $url): void
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        if ($path !== '' && str_starts_with($path, '/curators/') && $path !== '/curators/login') {
            Session::put('login_redirect', $url);
        }
    }

    private function pullCuratorRedirectOrDefault(bool $clear = true): string
    {
        $redirectTo = $clear ? Session::pull('login_redirect') : Session::get('login_redirect');
        if (is_string($redirectTo) && $redirectTo !== '') {
            $path = parse_url($redirectTo, PHP_URL_PATH) ?: '';
            if ($path !== '' && str_starts_with($path, '/curators/') && $path !== '/curators/login') {
                return $redirectTo;
            }
        }

        return route('curators.dashboard');
    }
}
