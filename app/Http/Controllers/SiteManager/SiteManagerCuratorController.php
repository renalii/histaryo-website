<?php

namespace App\Http\Controllers\SiteManager;

use App\Http\Controllers\Controller;
use App\Mail\CuratorPasswordResetMail;
use App\Services\CuratorWelcomeMailer;
use App\Services\FirebaseService;
use App\Services\SiteManagerLandmarks;
use App\Services\SiteManagerReadModel;
use App\Support\TemporaryPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Kreait\Firebase\Exception\Auth\EmailExists;

class SiteManagerCuratorController extends Controller
{
    public function __construct(
        protected FirebaseService $firebase,
        protected SiteManagerLandmarks $siteManagerLandmarks,
        protected CuratorWelcomeMailer $curatorWelcomeMailer,
        protected SiteManagerReadModel $siteManagerReadModel,
    ) {}

    public function create()
    {
        return redirect()->route('sitemanager.curators', ['create' => 1]);
    }

    protected function redirectToCuratorsDrawer()
    {
        return redirect()->route('sitemanager.curators', ['create' => 1]);
    }

    public function store(Request $request)
    {
        $managerUid = (string) Session::get('uid', '');
        $assignmentLandmarks = $this->siteManagerLandmarks->curatorAssignmentLandmarks($managerUid);
        $assignable = $assignmentLandmarks['assignable'];
        $assignableIds = array_column($assignable, 'id');

        if ($assignableIds === []) {
            return $this->redirectToCuratorsDrawer()
                ->with('status_err', 'Create and activate a landmark before adding curators.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:120',
            'last_name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'assigned_landmark_id' => ['required', 'string', Rule::in($assignableIds)],
        ], [
            'assigned_landmark_id.in' => 'Choose an active landmark from your portfolio.',
            'assigned_landmark_id.required' => 'Select the landmark this curator will manage.',
        ]);

        if (! $this->siteManagerLandmarks->managerMayAssignLandmark($managerUid, $validated['assigned_landmark_id'])) {
            return $this->redirectToCuratorsDrawer()->withErrors([
                'assigned_landmark_id' => 'That landmark is not active or is outside your portfolio.',
            ])->withInput();
        }

        $firstName = trim($validated['first_name']);
        $lastName = trim($validated['last_name']);
        $name = trim($firstName.' '.$lastName);
        $email = strtolower(trim($validated['email']));
        $password = TemporaryPassword::generate();
        $landmarkId = $validated['assigned_landmark_id'];
        $landmarkLabel = $this->siteManagerLandmarks->landmarkLabel($landmarkId) ?? 'Assigned landmark';
        $now = now()->toDateTimeString();

        try {
            $user = $this->firebase->createUser($email, $password, $name);
            $uid = $user->uid;

            $this->firebase->getAuth()->setCustomUserClaims($uid, ['role' => 'curator']);

            $this->firebase->userDocument($uid, 'curator')->set([
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => $name,
                'role' => 'curator',
                'approval_status' => 'approved',
                'requires_approval' => false,
                'account_status' => 'active',
                'curator_registration_type' => 'site_manager_invite',
                'assigned_landmark_id' => $landmarkId,
                'created_by_manager_uid' => $managerUid,
                'must_change_password' => true,
                'approved_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $mailResult = $this->curatorWelcomeMailer->send(
                firstName: $firstName,
                lastName: $lastName,
                email: $email,
                plainPassword: $password,
                landmarkLabel: $landmarkLabel,
                uid: $uid,
            );
            $emailSent = $mailResult['sent'];
            $emailError = $mailResult['error'];

            $this->firebase->firestore()->collection('logs')->add([
                'email' => Session::get('email'),
                'role' => 'site_manager',
                'action' => 'Site Manager created curator account: '.$email,
                'curator_email' => $email,
                'curator_uid' => $uid,
                'landmark_id' => $landmarkId,
                'email_notification' => $emailSent ? 'sent' : 'failed',
                'timestamp' => now()->toISOString(),
            ]);
            $this->siteManagerReadModel->forget($managerUid);

            if ($emailSent) {
                $status = 'Curator account created successfully. A welcome email with account details '
                    .'and a password setup link has been sent to the curator\'s email address.';
            } else {
                $status = 'Curator account created for '.$email.', but the welcome email could not be sent'
                    .($emailError ? ': '.$emailError : '. Check your mail settings.');
            }

            return redirect()->route('sitemanager.curators')->with('status', $status);
        } catch (EmailExists $e) {
            return $this->redirectToCuratorsDrawer()->withErrors([
                'email' => 'That email is already registered. Use a different address or manage the existing account under Curators.',
            ])->withInput();
        } catch (\Throwable $e) {
            report($e);

            return $this->redirectToCuratorsDrawer()->withErrors([
                'error' => 'Could not create curator account: '.$e->getMessage(),
            ])->withInput();
        }
    }

    public function update(Request $request, string $uid)
    {
        $managerUid = (string) Session::get('uid', '');
        $profile = $this->curatorProfileForManager($uid, $managerUid);
        if ($profile === null) {
            return redirect()->route('sitemanager.curators')
                ->with('status_err', 'Curator not found in your portfolio.');
        }

        $assignmentLandmarks = $this->siteManagerLandmarks->curatorAssignmentLandmarks($managerUid);
        $assignableIds = array_column($assignmentLandmarks['assignable'], 'id');

        $validated = $request->validate([
            'first_name' => 'required|string|max:120',
            'last_name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'assigned_landmark_id' => ['required', 'string', Rule::in($assignableIds)],
        ], [
            'assigned_landmark_id.in' => 'Choose an active landmark from your portfolio.',
            'assigned_landmark_id.required' => 'Select the landmark this curator will manage.',
        ]);

        if (! $this->siteManagerLandmarks->managerMayAssignLandmark($managerUid, $validated['assigned_landmark_id'])) {
            return redirect()->route('sitemanager.curators', ['edit' => $uid])->withErrors([
                'assigned_landmark_id' => 'That landmark is not active or is outside your portfolio.',
            ])->withInput();
        }

        $firstName = trim($validated['first_name']);
        $lastName = trim($validated['last_name']);
        $name = trim($firstName.' '.$lastName);
        $email = strtolower(trim($validated['email']));
        $now = now()->toDateTimeString();

        try {
            $authUpdate = [
                'email' => $email,
                'displayName' => $name,
            ];

            $this->firebase->getAuth()->updateUser($uid, $authUpdate);

            $this->firebase->userDocument($uid, 'curator')->set([
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => $name,
                'assigned_landmark_id' => $validated['assigned_landmark_id'],
                'updated_at' => $now,
            ], ['merge' => true]);

            $this->firebase->firestore()->collection('logs')->add([
                'email' => Session::get('email'),
                'role' => 'site_manager',
                'action' => 'Site Manager updated curator account: '.$email,
                'curator_email' => $email,
                'curator_uid' => $uid,
                'landmark_id' => $validated['assigned_landmark_id'],
                'timestamp' => now()->toISOString(),
            ]);
            $this->siteManagerReadModel->forget($managerUid);

            return redirect()->route('sitemanager.curators')->with('status', 'Curator updated successfully.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('sitemanager.curators', ['edit' => $uid])->withErrors([
                'error' => 'Could not update curator: '.$e->getMessage(),
            ])->withInput();
        }
    }

    public function destroy(string $uid)
    {
        $managerUid = (string) Session::get('uid', '');
        $profile = $this->curatorProfileForManager($uid, $managerUid);
        if ($profile === null) {
            return redirect()->route('sitemanager.curators')
                ->with('status_err', 'Curator not found in your portfolio.');
        }

        try {
            $this->firebase->getAuth()->deleteUser($uid);
            $this->firebase->userDocument($uid, 'curator')->delete();
            $this->siteManagerReadModel->forget($managerUid);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('sitemanager.curators')
                ->with('status_err', 'Could not delete curator account: '.$e->getMessage());
        }

        try {
            $this->firebase->firestore()->collection('logs')->add([
                'email' => Session::get('email'),
                'role' => 'site_manager',
                'action' => 'Site Manager deleted curator account: '.($profile['email'] ?? $uid),
                'curator_email' => $profile['email'] ?? '',
                'curator_uid' => $uid,
                'landmark_id' => $profile['assigned_landmark_id'] ?? '',
                'timestamp' => now()->toISOString(),
            ]);
            $this->siteManagerReadModel->forget($managerUid);
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('sitemanager.curators')->with('status', 'Curator account deleted successfully.');
    }

    public function activate(Request $request, string $uid)
    {
        return $this->updateAccountStatus($request, $uid, 'active');
    }

    public function deactivate(Request $request, string $uid)
    {
        return $this->updateAccountStatus($request, $uid, 'inactive');
    }

    public function sendPasswordReset(string $uid)
    {
        $managerUid = (string) Session::get('uid', '');
        $profile = $this->curatorProfileForManager($uid, $managerUid);
        if ($profile === null) {
            return redirect()->route('sitemanager.curators')
                ->with('status_err', 'Unable to send password reset email. Please try again.');
        }

        $email = strtolower(trim((string) ($profile['email'] ?? '')));

        try {
            $resetUrl = $this->firebase->getAuth()->getPasswordResetLink($email);
            Mail::send(new CuratorPasswordResetMail($email, $resetUrl));

            return redirect()->route('sitemanager.curators', ['edit' => $uid])
                ->with('status', 'Password reset email sent successfully.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('sitemanager.curators', ['edit' => $uid])
                ->with('status_err', 'Unable to send password reset email. Please try again.');
        }
    }

    private function updateAccountStatus(Request $request, string $uid, string $accountStatus)
    {
        $managerUid = (string) Session::get('uid', '');
        $profile = $this->curatorProfileForManager($uid, $managerUid);
        $isActivating = $accountStatus === 'active';
        if ($profile === null) {
            return redirect()->route('sitemanager.curators', $request->only(['search', 'page']))
                ->with('status_err', 'Curator not found in your portfolio.');
        }

        try {
            $this->firebase->userDocument($uid, 'curator')->set([
                'account_status' => $accountStatus,
                'updated_at' => now()->toDateTimeString(),
            ], ['merge' => true]);
            $this->siteManagerReadModel->forget($managerUid);

            $this->firebase->firestore()->collection('logs')->add([
                'email' => Session::get('email'),
                'role' => 'site_manager',
                'action' => 'Site Manager '.($isActivating ? 'activated' : 'deactivated').' curator account: '.($profile['email'] ?? $uid),
                'curator_email' => $profile['email'] ?? '',
                'curator_uid' => $uid,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('sitemanager.curators', $request->only(['search', 'page']))
                ->with('status_err', 'Could not '.($isActivating ? 'activate' : 'deactivate').' curator account: '.$e->getMessage());
        }

        return redirect()->route('sitemanager.curators', $request->only(['search', 'page']))
            ->with('status', 'Curator '.($isActivating ? 'activated' : 'deactivated').' successfully.');
    }

    private function curatorProfileForManager(string $uid, string $managerUid): ?array
    {
        $uid = trim($uid);
        $managerUid = trim($managerUid);
        if ($uid === '' || $managerUid === '') {
            return null;
        }

        $profile = $this->firebase->userProfile($uid, 'curator');
        if ($profile === null) {
            return null;
        }

        $data = $profile['data'];
        if (($data['role'] ?? '') !== 'curator') {
            return null;
        }

        $landmarkId = trim((string) ($data['assigned_landmark_id'] ?? ''));
        if ($landmarkId === '') {
            return null;
        }

        $snap = $this->firebase->firestore()->collection('landmarks')->document($landmarkId)->snapshot();
        if (! $snap->exists()) {
            return null;
        }

        $siteManagerUid = trim((string) ($snap->data()['manager_uid'] ?? $snap->data()['managerUid'] ?? ''));

        return $siteManagerUid !== '' && $siteManagerUid === $managerUid ? $data : null;
    }
}
