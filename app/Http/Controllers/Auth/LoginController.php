<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\FirestoreBool;
use App\Support\UserApprovalPolicy;
use Illuminate\Http\Request;
use App\Services\CuratorAccessibleLandmarks;
use App\Services\CuratorBrowseableLandmarks;
use App\Services\FirebaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Kreait\Firebase\Auth\SignIn\FailedToSignIn;
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
            if ($role === 'landmark_manager') {
                Session::put('role', 'site_manager');
                $role = 'site_manager';
            }

            if ($role === 'curator') {
                if (Session::get('must_change_password')) {
                    return redirect()->route('curators.change-password');
                }
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

            if ($role === 'site_manager') {
                return redirect()->route('sitemanager.dashboard');
            }
        }

        return response()
            ->view('auth.login')
            ->withHeaders($this->noAuthPageCacheHeaders());
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

            $profile = $this->firebase->userProfile($uid, is_string($role) ? $role : null);
            $userData = $profile['data'] ?? [];
            $name = $profile ? ($userData['name'] ?? null) : null;
            $fsRole = $profile
                ? (string) $profile['role']
                : $this->firebase->normalizeUserRole(is_string($role) ? $role : null);

            if ($profile && $fsRole === 'visitor') {
                $visitorPatch = UserApprovalPolicy::visitorAutoApprovalPatch($userData);
                if ($visitorPatch !== null) {
                    $profile['ref']->set($visitorPatch, ['merge' => true]);
                    $userData = array_merge($userData, $visitorPatch);
                }
            }

            $approvalStatus = $profile
                ? strtolower((string) ($userData['approval_status'] ?? 'approved'))
                : 'approved';
            $requiresApproval = $profile
                ? UserApprovalPolicy::effectiveRequiresApproval($fsRole, $userData['requires_approval'] ?? null)
                : false;

            if ($fsRole === 'visitor') {
                $approvalStatus = 'approved';
                $requiresApproval = false;

                Log::info('Visitor login loaded Firestore profile.', [
                    'uid' => $uid,
                    'collection' => $profile['collection'] ?? $this->firebase->userCollectionPath('visitor'),
                    'profile_found' => $profile !== null,
                ]);
            }

            if ($fsRole === 'curator' && strtolower((string) ($userData['account_status'] ?? 'active')) === 'inactive') {
                return back()->withErrors([
                    'error' => 'Your curator account is inactive. Please contact your Site Manager.',
                ]);
            }

            if ($requiresApproval && $approvalStatus === 'rejected') {
                return back()->withErrors([
                    'error' => 'Your registration was not approved by the administrator. Please contact support if you have questions.',
                ]);
            }

            if ($requiresApproval && $approvalStatus !== 'approved') {
                if ($fsRole === 'site_manager') {
                    $pendingMsg = 'Your account is waiting for administrator approval.';
                } elseif ($fsRole === 'curator') {
                    $pendingMsg = 'Your account is waiting for approval from your Site Manager.';
                } else {
                    $pendingMsg = 'Your account is pending approval before activation.';
                }

                return back()->withErrors(['error' => $pendingMsg]);
            }


            
            Session::put('uid', $uid);
            Session::put('role', $fsRole);
            Session::put('email', $email);
            if ($name) {
                Session::put('name', $name);
            }

            if ($fsRole === 'curator') {
                $assignedLandmarkIdRaw = $profile ? ($userData['assigned_landmark_id'] ?? '') : '';
                $assignedTrimmed = is_string($assignedLandmarkIdRaw) ? trim($assignedLandmarkIdRaw) : '';
                Session::put('account_status', strtolower((string) ($userData['account_status'] ?? 'active')));
                Session::put('assigned_landmark_id', $assignedTrimmed);
                Session::put('browseable_landmark_ids', CuratorBrowseableLandmarks::resolveIds($this->firebase, $assignedTrimmed));
                Session::put(
                    'writable_landmark_ids',
                    $assignedTrimmed !== ''
                        ? CuratorAccessibleLandmarks::resolveIds($this->firebase, $assignedTrimmed)
                        : []
                );
                if ($profile && FirestoreBool::isTrue($userData['must_change_password'] ?? false)) {
                    Session::put('must_change_password', true);
                } else {
                    Session::forget('must_change_password');
                }
            } else {
                Session::forget('assigned_landmark_id');
                Session::forget('account_status');
                Session::forget('browseable_landmark_ids');
                Session::forget('writable_landmark_ids');
            }

            
            $this->firebase->firestore()->collection('logs')->add([
                'email' => $email,
                'action' => 'Logged in',
                'timestamp' => now()->toISOString(),
            ]);

            
            if ($fsRole === 'admin') {
                return redirect()->route('admin.dashboard')->with('success', 'Welcome Admin!');
            } elseif ($fsRole === 'curator') {
                if (Session::get('must_change_password')) {
                    return redirect()
                        ->route('curators.change-password')
                        ->with('success', 'Welcome! Set a new password to continue.');
                }
                $assignedAfterLogin = Session::get('assigned_landmark_id');
                $redirectTo =
                    (! is_string($assignedAfterLogin) || trim($assignedAfterLogin) === '')
                        ? route('curators.pending-assignment')
                        : $this->pullCuratorRedirectOrDefault();
                return redirect()->to($redirectTo)->with('success', 'Welcome Curator!');
            } elseif ($fsRole === 'site_manager') {
                return redirect()->route('sitemanager.dashboard')->with('success', 'Welcome Site Manager!');
            } else {
                return back()->withErrors(['error' => 'Unauthorized role.']);
            }

        } catch (UserNotFound | InvalidPassword | FailedToSignIn $e) {
            return back()->withErrors([
                'error' => 'Login failed. Please check your username/email and password, then try again.',
            ]);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'INVALID_LOGIN_CREDENTIALS')
                || str_contains($message, 'INVALID_PASSWORD')
                || str_contains($message, 'EMAIL_NOT_FOUND')) {
                return back()->withErrors([
                    'error' => 'Login failed. Please check your username/email and password, then try again.',
                ]);
            }

            return back()->withErrors(['error' => 'Login failed. Please try again later.']);
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

        return redirect('/login')->with('success', 'Logged out successfully.');
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

    private function noAuthPageCacheHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
    }
}
