<?php

namespace App\Http\Controllers\LandmarkManager;

use App\Http\Controllers\Controller;
use App\Services\CuratorWelcomeMailer;
use App\Services\FirebaseService;
use App\Services\SiteManagerLandmarks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Kreait\Firebase\Exception\Auth\EmailExists;

class CuratorAccountController extends Controller
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
            $message = $assignmentLandmarks['all_active_assigned']
                ? 'Every active landmark already has a curator. Free a landmark or add another landmark before creating a new curator.'
                : 'Create and activate a landmark before adding curators.';

            return $this->redirectToCuratorsDrawer()
                ->with('status_err', $message);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:120',
            'last_name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'assigned_landmark_id' => ['required', 'string', Rule::in($assignableIds)],
        ], [
            'assigned_landmark_id.in' => 'Choose an active, unassigned landmark from your portfolio.',
            'assigned_landmark_id.required' => 'Select the landmark this curator will manage.',
        ]);

        if (! $this->siteManagerLandmarks->managerMayAssignLandmark($managerUid, $validated['assigned_landmark_id'])) {
            return $this->redirectToCuratorsDrawer()->withErrors([
                'assigned_landmark_id' => 'That landmark is not available—it may already have a curator or is outside your portfolio.',
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

            $this->firebase->firestore()->collection('users')->document($uid)->set([
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => $name,
                'role' => 'curator',
                'approval_status' => 'approved',
                'requires_approval' => false,
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
}
