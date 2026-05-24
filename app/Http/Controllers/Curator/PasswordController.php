<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use App\Services\CuratorAccessibleLandmarks;
use App\Services\CuratorBrowseableLandmarks;
use App\Services\FirebaseService;
use App\Support\FirestoreBool;
use App\Support\PublicAppUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Kreait\Firebase\Exception\Auth\InvalidPassword;
use Kreait\Firebase\Exception\Auth\UserNotFound;

class PasswordController extends Controller
{
    public function __construct(protected FirebaseService $firebase) {}

    public function showSetupForm(Request $request): View|RedirectResponse
    {
        $uid = (string) $request->query('uid', '');
        if ($uid === '') {
            return redirect()->route('curators.login')->withErrors([
                'error' => 'This password setup link is invalid.',
            ]);
        }

        $userDoc = $this->firebase->firestore()->collection('users')->document($uid)->snapshot();
        if (! $userDoc->exists() || strtolower((string) ($userDoc['role'] ?? '')) !== 'curator') {
            return redirect()->route('curators.login')->withErrors([
                'error' => 'This password setup link is invalid or has expired.',
            ]);
        }

        if (! FirestoreBool::isTrue($userDoc['must_change_password'] ?? false)) {
            return redirect()->route('curators.login')->with('success', 'Your password is already set. Sign in to continue.');
        }

        $expiresAt = $request->query('expires');
        $signedAction = $expiresAt
            ? PublicAppUrl::temporarySignedRoute(
                'curators.setup-password.update',
                Carbon::createFromTimestamp((int) $expiresAt),
                ['uid' => $uid]
            )
            : PublicAppUrl::signedRoute('curators.setup-password.update', ['uid' => $uid]);

        return view('curators.setup-password', [
            'uid' => $uid,
            'email' => (string) ($userDoc['email'] ?? ''),
            'curatorName' => (string) ($userDoc['name'] ?? 'Curator'),
            'signedAction' => $signedAction,
        ]);
    }

    public function completeSetup(Request $request): RedirectResponse
    {
        $uid = (string) $request->query('uid', '');
        if ($uid === '') {
            return redirect()->route('curators.login')->withErrors([
                'error' => 'This password setup link is invalid.',
            ]);
        }

        $userDoc = $this->firebase->firestore()->collection('users')->document($uid)->snapshot();
        if (! $userDoc->exists() || strtolower((string) ($userDoc['role'] ?? '')) !== 'curator') {
            return redirect()->route('curators.login')->withErrors([
                'error' => 'This password setup link is invalid or has expired.',
            ]);
        }

        if (! FirestoreBool::isTrue($userDoc['must_change_password'] ?? false)) {
            return redirect()->route('curators.login')->with('success', 'Your password is already set. Sign in to continue.');
        }

        $email = strtolower(trim((string) ($userDoc['email'] ?? '')));
        if ($email === '') {
            return redirect()->route('curators.login')->withErrors([
                'error' => 'Could not complete password setup. Please contact your Site Manager.',
            ]);
        }

        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $this->firebase->getAuth()->changeUserPassword($uid, $validated['password']);

            $now = now()->toDateTimeString();
            $this->firebase->firestore()->collection('users')->document($uid)->set([
                'must_change_password' => false,
                'password_changed_at' => $now,
                'updated_at' => $now,
            ], ['merge' => true]);

            $signInResult = $this->firebase->getAuth()->signInWithEmailAndPassword($email, $validated['password']);
            $idToken = $signInResult->idToken();
            $firebaseUser = $this->firebase->getAuth()->verifyIdToken($idToken);
            $role = strtolower((string) ($userDoc['role'] ?? $firebaseUser->claims()->get('role') ?? 'curator'));

            $assignedLandmarkIdRaw = $userDoc['assigned_landmark_id'] ?? '';
            $assignedTrimmed = is_string($assignedLandmarkIdRaw) ? trim($assignedLandmarkIdRaw) : '';

            Session::put('uid', $uid);
            Session::put('role', $role);
            Session::put('email', $email);
            Session::put('name', (string) ($userDoc['name'] ?? ''));
            Session::put('assigned_landmark_id', $assignedTrimmed);
            Session::forget('must_change_password');
            Session::put('browseable_landmark_ids', CuratorBrowseableLandmarks::resolveIds($this->firebase, $assignedTrimmed));
            Session::put(
                'writable_landmark_ids',
                $assignedTrimmed !== ''
                    ? CuratorAccessibleLandmarks::resolveIds($this->firebase, $assignedTrimmed)
                    : []
            );

            $this->firebase->firestore()->collection('logs')->add([
                'email' => $email,
                'action' => 'Curator set password via invite link',
                'timestamp' => now()->toISOString(),
            ]);

            $destination = $assignedTrimmed === ''
                ? route('curators.pending-assignment')
                : route('curators.dashboard');

            return redirect()
                ->to($destination)
                ->with('success', 'Your password has been set. Welcome to the curator dashboard.');
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'error' => 'Could not set your password. Please try again or use the link from your welcome email.',
            ])->withInput();
        }
    }

    public function showChangeForm(): View|RedirectResponse
    {
        if (! Session::has('uid') || Session::get('role') !== 'curator') {
            return redirect()->route('curators.login');
        }

        if (! Session::get('must_change_password')) {
            return redirect()->to($this->curatorDestinationAfterGate());
        }

        return view('curators.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        if (! Session::has('uid') || Session::get('role') !== 'curator') {
            return redirect()->route('curators.login');
        }

        if (! Session::get('must_change_password')) {
            return redirect()->to($this->curatorDestinationAfterGate());
        }

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed|different:current_password',
        ], [
            'password.different' => 'Your new password must be different from the temporary password.',
        ]);

        $uid = (string) Session::get('uid');
        $email = (string) Session::get('email');

        try {
            $this->firebase->getAuth()->signInWithEmailAndPassword($email, $validated['current_password']);
        } catch (UserNotFound|InvalidPassword) {
            return back()->withErrors([
                'current_password' => 'The temporary password you entered is incorrect.',
            ])->withInput();
        }

        try {
            $this->firebase->getAuth()->changeUserPassword($uid, $validated['password']);

            $now = now()->toDateTimeString();
            $this->firebase->firestore()->collection('users')->document($uid)->set([
                'must_change_password' => false,
                'password_changed_at' => $now,
                'updated_at' => $now,
            ], ['merge' => true]);

            Session::forget('must_change_password');

            $this->firebase->firestore()->collection('logs')->add([
                'email' => $email,
                'action' => 'Curator changed password after invite',
                'timestamp' => now()->toISOString(),
            ]);

            return redirect()
                ->to($this->curatorDestinationAfterGate())
                ->with('success', 'Your password has been updated. You can now use the curator dashboard.');
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'error' => 'Could not update your password. Please try again.',
            ])->withInput();
        }
    }

    private function curatorDestinationAfterGate(): string
    {
        $assigned = Session::get('assigned_landmark_id');
        if (! is_string($assigned) || trim($assigned) === '') {
            return route('curators.pending-assignment');
        }

        $redirectTo = Session::pull('login_redirect');
        if (is_string($redirectTo) && $redirectTo !== '') {
            $path = parse_url($redirectTo, PHP_URL_PATH) ?: '';
            if ($path !== '' && str_starts_with($path, '/curators/') && $path !== '/curators/login') {
                return $redirectTo;
            }
        }

        return route('curators.dashboard');
    }
}
