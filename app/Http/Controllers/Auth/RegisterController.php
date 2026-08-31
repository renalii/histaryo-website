<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirebaseService;
use App\Services\SiteManagerCredentialStorage;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\Auth\EmailExists;

class RegisterController extends Controller
{
    protected $firebase;

    public function __construct(
        FirebaseService $firebase,
        protected SiteManagerCredentialStorage $credentialStorage
    )
    {
        $this->firebase = $firebase;
    }

    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
            'first_name' => 'required|string|max:120',
            'last_name' => 'required|string|max:120',
            'credentials_file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx',
                'max:5120',
            ],
        ];

        $request->validate($rules);

        $email = $request->email;
        $password = $request->password;
        $firstName = trim($request->first_name);
        $lastName = trim($request->last_name);
        $name = trim($firstName . ' ' . $lastName);
        $role = 'site_manager';
        $approvalStatus = 'approved';
        $requiresApproval = false;

        if ($role === 'site_manager') {
            $requiresApproval = true;
            $approvalStatus = 'pending';
        }

        try {
            $user = $this->firebase->createUser($email, $password, $name);
            $uid = $user->uid;

            $credentialDocument = $this->credentialStorage->store($request->file('credentials_file'), $uid);

            $this->firebase->getAuth()->setCustomUserClaims($uid, ['role' => $role]);

            $userData = [
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => $name,
                'role' => $role,
                'approval_status' => $approvalStatus,
                'requires_approval' => $requiresApproval,
                'created_at' => now()->toDateTimeString(),
                'credentials_document' => $credentialDocument,
            ];

            $this->firebase->userDocument($uid, $role)->set($userData);
            $profilePath = $this->firebase->userCollectionPath($role);
            $createdSnapshot = $this->firebase->userDocument($uid, $role)->snapshot();
            if (! $createdSnapshot->exists()) {
                Log::error('User registration profile was not created in expected Firestore collection.', [
                    'uid' => $uid,
                    'role' => $role,
                    'collection' => $profilePath,
                ]);

                throw new \RuntimeException('Registration profile was not saved in the expected Firestore collection.');
            }

            if ($requiresApproval && $role === 'site_manager') {
                $successMessage = 'Registration submitted successfully! Your account is pending admin approval.';
            } else {
                $successMessage = $requiresApproval
                    ? 'Registration submitted successfully! Pending approval.'
                    : 'Registration successful! Please log in.';
            }

            return redirect()->route('login')->with('success', $successMessage);
        } catch (EmailExists $e) {
            return back()->withErrors(['error' => 'The email is already registered. Please use a different one.'])->withInput();
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Registration failed. '.$e->getMessage()])->withInput();
        }
    }
}
