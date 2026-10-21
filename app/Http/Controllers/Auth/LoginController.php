<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\FirestoreBool;
use App\Support\UserApprovalPolicy;
use Illuminate\Http\Request;
use App\Services\CuratorAccessibleLandmarks;
use App\Services\CuratorBrowseableLandmarks;
use App\Services\FirebaseService;
use App\Services\MfaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Kreait\Firebase\Auth\SignIn\FailedToSignIn;
use Kreait\Firebase\Exception\Auth\InvalidPassword;
use Kreait\Firebase\Exception\Auth\UserNotFound;

class LoginController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase, private MfaService $mfa)
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
            $signInResult = $this->firebase->signInWithEmailAndPassword($email, $password);
            $uid = trim((string) ($signInResult['localId'] ?? ''));
            $role = $this->roleFromIdToken((string) ($signInResult['idToken'] ?? ''));

            if ($uid === '') {
                Log::warning('Firebase login response did not include a user id.', [
                    'email' => $email,
                ]);

                return back()->withErrors(['error' => 'Login failed. Please try again later.']);
            }

            $profile = $this->firebase->userProfile($uid, is_string($role) ? $role : null);
            $userData = $profile['data'] ?? [];
            $name = $profile ? ($userData['name'] ?? null) : null;
            $fsRole = $profile
                ? (string) $profile['role']
                : $this->firebase->normalizeUserRole(is_string($role) ? $role : null);

            if (! in_array($fsRole, ['admin', 'curator', 'site_manager'], true)) {
                return back()->withErrors(['error' => 'Unauthorized role.']);
            }

            $approvalStatus = $profile
                ? strtolower((string) ($userData['approval_status'] ?? 'approved'))
                : 'approved';
            $requiresApproval = $profile
                ? UserApprovalPolicy::effectiveRequiresApproval($fsRole, $userData['requires_approval'] ?? null)
                : false;

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


            
            if (in_array($fsRole, ['curator', 'site_manager'], true)) {
                $this->beginMfaChallenge($uid, $email, $fsRole, $userData, $name);
                return redirect()->route('login.mfa');
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
                'error' => $this->loginFailureMessage($e, $email),
            ]);
        } catch (\Exception $e) {
            if ($this->isCredentialFailure($e)) {
                return back()->withErrors([
                    'error' => $this->loginFailureMessage($e, $email),
                ]);
            }

            Log::warning('Login failed due to backend exception.', [
                'email' => $email,
                'exception' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Login failed. Please try again later.']);
        }
    }

    public function showMfaForm()
    {
        if (! Session::has('mfa_pending')) {
            return redirect()->route('login');
        }

        $pending = Session::get('mfa_pending');
        if (! is_array($pending) || (int) ($pending['expires_at'] ?? 0) < time()) {
            Session::forget('mfa_pending');

            return redirect()->route('login')->withErrors([
                'error' => 'Your login challenge expired. Please sign in again.',
            ]);
        }

        return response()->view('auth.mfa', [
            'email' => $this->maskedEmail((string) ($pending['email'] ?? '')),
            'methods' => $pending['methods'] ?? [MfaService::EMAIL],
            'emailSent' => (bool) ($pending['email_sent'] ?? true),
            'resendAvailableIn' => RateLimiter::availableIn(
                'mfa:resend:'.sha1((string) $pending['uid'].'|'.request()->ip())
            ),
        ])->withHeaders($this->noAuthPageCacheHeaders());
    }

    public function verifyMfa(Request $request)
    {
        $pending = Session::get('mfa_pending');
        if (! is_array($pending) || empty($pending['uid'])) {
            return redirect()->route('login')->withErrors(['error' => 'Your login challenge expired. Please sign in again.']);
        }

        // Keep both limits: the account limit prevents distributed guessing and
        // the IP limit prevents one client from consuming many account attempts.
        $accountKey = 'mfa:account:'.sha1((string) $pending['uid']);
        $ipKey = 'mfa:ip:'.sha1((string) $pending['uid'].'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($accountKey, 5) || RateLimiter::tooManyAttempts($ipKey, 5)) {
            return back()->withErrors(['code' => 'Too many attempts. Please wait and request a new code.']);
        }

        $method = $request->string('method')->toString();
        $request->validate([
            'code' => [
                'required',
                'string',
                $method === MfaService::RECOVERY ? 'max:32' : 'digits:6',
            ],
            'method' => 'required|in:email,authenticator,recovery',
        ]);
        if (! in_array($method, $pending['methods'] ?? [MfaService::EMAIL], true)) {
            return back()->withErrors(['code' => 'That authentication method is not available.']);
        }
        $valid = (int) ($pending['expires_at'] ?? 0) >= time();
        $sessionKey = (string) Session::get('mfa_session_key', '');
        if ($method === MfaService::EMAIL) {
            $valid = $valid && $this->mfa->verifyEmailChallenge(
                (string) ($pending['challenge_id'] ?? ''),
                $sessionKey,
                $request->string('code')->toString(),
            );
        } elseif ($method === MfaService::TOTP) {
            $valid = $valid && $this->mfa->verifyTotp(
                (string) $pending['uid'],
                (string) $pending['role'],
                $request->string('code')->toString(),
            );
        } else {
            $valid = $valid && $this->mfa->consumeRecoveryCode(
                (string) $pending['uid'],
                (string) $pending['role'],
                $request->string('code')->toString(),
            );
        }

        if (! $valid) {
            RateLimiter::hit($accountKey, 300);
            RateLimiter::hit($ipKey, 300);
            return back()->withErrors(['code' => 'The verification code is invalid or expired.']);
        }

        RateLimiter::clear($accountKey);
        RateLimiter::clear($ipKey);
        Session::forget('mfa_pending');
        Session::forget('mfa_session_key');
        Session::regenerate();
        Session::put('mfa_verified', true);

        return $this->finishMfaLogin($pending);
    }

    public function cancelMfa(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('mfa_pending');
        if (is_array($pending)) {
            $this->mfa->invalidateChallenge((string) ($pending['challenge_id'] ?? ''), (string) $request->session()->get('mfa_session_key', ''));
        }
        $request->session()->forget(['mfa_pending', 'mfa_session_key', 'mfa_verified']);
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Sign-in cancelled.');
    }

    public function resendMfa(Request $request): RedirectResponse
    {
        $pending = Session::get('mfa_pending');
        $sessionKey = (string) Session::get('mfa_session_key', '');
        if (! is_array($pending) || $sessionKey === '') {
            return redirect()->route('login')->withErrors(['error' => 'Your login challenge expired. Please sign in again.']);
        }
        if (! in_array(MfaService::EMAIL, $pending['methods'] ?? [], true)) {
            return back()->withErrors(['code' => 'Email verification is not available for this account.']);
        }
        $key = 'mfa:resend:'.sha1((string) $pending['uid'].'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 1)) {
            return back()->withErrors(['code' => 'Please wait before requesting another code.']);
        }
        RateLimiter::hit($key, 60);
        $this->mfa->invalidateChallenge((string) $pending['challenge_id'], $sessionKey);
        $issued = $this->mfa->issueEmailChallenge((string) $pending['uid'], (string) $pending['role'], $sessionKey, $request->ip());
        [$challengeId, $code] = explode('|', $issued, 2);
        $pending['challenge_id'] = $challengeId;
        $pending['expires_at'] = time() + 300;
        $pending['email_sent'] = true;
        Session::put('mfa_pending', $pending);
        Mail::raw("Your HistARyo verification code is {$code}. It expires in 5 minutes.", function ($message) use ($pending): void {
            $message->to((string) $pending['email'])->subject('HistARyo sign-in verification code');
        });
        return back()->with('status', 'A new verification code was sent.');
    }

    private function beginMfaChallenge(string $uid, string $email, string $role, array $userData, ?string $name): void
    {
        Session::regenerate();
        $sessionKey = Str::random(64);
        $issued = $this->mfa->issueEmailChallenge($uid, $role, $sessionKey, request()->ip());
        [$challengeId, $code] = explode('|', $issued, 2);
        $methods = $this->mfa->methods($uid, $role);
        $sendEmailNow = ! in_array(MfaService::TOTP, $methods, true);
        Session::put('mfa_pending', [
            'uid' => $uid,
            'email' => $email,
            'role' => $role,
            'name' => $name,
            // Keep only fields needed after verification; do not put the full
            // Firestore profile in the session payload.
            'user_data' => [
                'assigned_landmark_id' => $userData['assigned_landmark_id'] ?? '',
                'account_status' => $userData['account_status'] ?? 'active',
                'must_change_password' => $userData['must_change_password'] ?? false,
            ],
            'challenge_id' => $challengeId,
            'expires_at' => time() + 300,
            'methods' => $methods,
            'email_sent' => $sendEmailNow,
        ]);
        Session::put('mfa_session_key', $sessionKey);
        Session::forget(['uid', 'role', 'mfa_verified']);
        Session::forget([
            'must_change_password',
            'assigned_landmark_id',
            'account_status',
            'browseable_landmark_ids',
            'writable_landmark_ids',
        ]);

        if ($sendEmailNow) {
            Mail::raw("Your HistARyo verification code is {$code}. It expires in 5 minutes.", function ($message) use ($email): void {
                $message->to($email)->subject('HistARyo sign-in verification code');
            });
            RateLimiter::hit('mfa:resend:'.sha1($uid.'|'.request()->ip()), 60);
        }
    }

    private function finishMfaLogin(array $pending): RedirectResponse
    {
        $uid = (string) $pending['uid'];
        $email = (string) $pending['email'];
        $role = (string) $pending['role'];
        $userData = is_array($pending['user_data'] ?? null) ? $pending['user_data'] : [];

        Session::put(['uid' => $uid, 'role' => $role, 'email' => $email, 'mfa_verified' => true]);
        if (! empty($pending['name'])) {
            Session::put('name', $pending['name']);
        }

        if ($role === 'curator') {
            $assigned = trim((string) ($userData['assigned_landmark_id'] ?? ''));
            Session::put('account_status', strtolower((string) ($userData['account_status'] ?? 'active')));
            Session::put('assigned_landmark_id', $assigned);
            Session::put('browseable_landmark_ids', CuratorBrowseableLandmarks::resolveIds($this->firebase, $assigned));
            Session::put('writable_landmark_ids', $assigned !== '' ? CuratorAccessibleLandmarks::resolveIds($this->firebase, $assigned) : []);
            if (FirestoreBool::isTrue($userData['must_change_password'] ?? false)) {
                Session::put('must_change_password', true);
            }

            if (Session::get('must_change_password')) {
                return redirect()->route('curators.change-password');
            }

            return redirect()->to($assigned === '' ? route('curators.pending-assignment') : $this->pullCuratorRedirectOrDefault());
        }

        Session::forget([
            'must_change_password',
            'assigned_landmark_id',
            'account_status',
            'browseable_landmark_ids',
            'writable_landmark_ids',
        ]);

        return redirect()->route('sitemanager.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
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

    private function noAuthPageCacheHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
    }

    private function maskedEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '' || $domain === '') {
            return 'your email address';
        }

        $visible = mb_substr($local, 0, 1);

        return $visible.str_repeat('*', max(1, mb_strlen($local) - 1)).'@'.$domain;
    }

    private function roleFromIdToken(string $idToken): ?string
    {
        $parts = explode('.', $idToken);
        if (count($parts) < 2) {
            return null;
        }

        $payload = $this->base64UrlDecode($parts[1]);
        if ($payload === null) {
            return null;
        }

        $claims = json_decode($payload, true);
        if (! is_array($claims)) {
            return null;
        }

        $role = $claims['role'] ?? null;

        return is_string($role) ? $role : null;
    }

    private function base64UrlDecode(string $value): ?string
    {
        $value = strtr($value, '-_', '+/');
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($value, true);

        return is_string($decoded) ? $decoded : null;
    }

    private function loginFailureMessage(\Throwable $exception, string $email): string
    {
        if ($exception instanceof UserNotFound || $this->exceptionContains($exception, 'EMAIL_NOT_FOUND')) {
            return 'Email not found. Please check your email and try again.';
        }

        if ($exception instanceof InvalidPassword || $this->exceptionContains($exception, 'INVALID_PASSWORD')) {
            return 'Incorrect password. Please try again.';
        }

        if ($this->exceptionContains($exception, 'INVALID_LOGIN_CREDENTIALS')) {
            $emailExists = $this->emailExists($email);

            return match ($emailExists) {
                true => 'Incorrect password. Please try again.',
                false => 'Email not found. Please check your email and try again.',
                default => 'Invalid email or password. Please try again.',
            };
        }

        return 'Invalid email or password. Please try again.';
    }

    private function isCredentialFailure(\Throwable $exception): bool
    {
        return $exception instanceof UserNotFound
            || $exception instanceof InvalidPassword
            || $exception instanceof FailedToSignIn
            || $this->exceptionContains($exception, 'INVALID_LOGIN_CREDENTIALS')
            || $this->exceptionContains($exception, 'INVALID_PASSWORD')
            || $this->exceptionContains($exception, 'EMAIL_NOT_FOUND');
    }

    private function exceptionContains(\Throwable $exception, string $needle): bool
    {
        return str_contains(strtoupper($exception->getMessage()), $needle);
    }

    private function emailExists(string $email): ?bool
    {
        try {
            $this->firebase->getAuth()->getUserByEmail($email);

            return true;
        } catch (UserNotFound) {
            return false;
        } catch (\Throwable) {
            return null;
        }
    }

}
