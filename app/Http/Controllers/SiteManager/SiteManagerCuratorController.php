<?php

namespace App\Http\Controllers\SiteManager;

use App\Http\Controllers\Controller;
use App\Services\CuratorWelcomeMailer;
use App\Services\FirebaseService;
use App\Services\SiteManagerLandmarks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Kreait\Firebase\Exception\Auth\EmailExists;

class SiteManagerCuratorController extends Controller
{
    public function __construct(
        protected FirebaseService $firebase,
        protected SiteManagerLandmarks $siteManagerLandmarks,
        protected CuratorWelcomeMailer $curatorWelcomeMailer,
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
            'password' => 'required|string|min:8|confirmed',
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
        $password = $validated['password'];
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
                'action' => 'Site Manager created curator account',
                'curator_email' => $email,
                'curator_uid' => $uid,
                'landmark_id' => $landmarkId,
                'email_notification' => $emailSent ? 'sent' : 'failed',
                'timestamp' => now()->toISOString(),
            ]);

            if ($emailSent) {
                $status = 'Curator account created successfully. A welcome email with account details '
                    .'and a password reset link has been sent to the curator\'s email address.';
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
            $this->firebase->getAuth()->updateUser($uid, [
                'email' => $email,
                'displayName' => $name,
            ]);

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
                'action' => 'Site Manager updated curator account',
                'curator_email' => $email,
                'curator_uid' => $uid,
                'landmark_id' => $validated['assigned_landmark_id'],
                'timestamp' => now()->toISOString(),
            ]);

            return redirect()->route('sitemanager.curators')->with('status', 'Curator updated successfully.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('sitemanager.curators', ['edit' => $uid])->withErrors([
                'error' => 'Could not update curator: '.$e->getMessage(),
            ])->withInput();
        }
    }

    public function deactivate(string $uid)
    {
        return $this->setAccountStatus($uid, 'inactive');
    }

    public function activate(string $uid)
    {
        return $this->setAccountStatus($uid, 'active');
    }

    private function setAccountStatus(string $uid, string $status)
    {
        $managerUid = (string) Session::get('uid', '');
        $profile = $this->curatorProfileForManager($uid, $managerUid);
        if ($profile === null) {
            return redirect()->route('sitemanager.curators')
                ->with('status_err', 'Curator not found in your portfolio.');
        }

        $now = now()->toDateTimeString();
        $this->firebase->userDocument($uid, 'curator')->set([
            'account_status' => $status,
            'updated_at' => $now,
        ], ['merge' => true]);

        $this->firebase->firestore()->collection('logs')->add([
            'email' => Session::get('email'),
            'action' => 'Site Manager '.($status === 'active' ? 'activated' : 'deactivated').' curator account',
            'curator_email' => $profile['email'] ?? '',
            'curator_uid' => $uid,
            'timestamp' => now()->toISOString(),
        ]);

        return redirect()->route('sitemanager.curators')
            ->with('status', 'Curator '.($status === 'active' ? 'activated' : 'deactivated').' successfully.');
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
