<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Validation\Rule;
use Kreait\Firebase\Exception\Auth\EmailExists;

class RegisterController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
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
            'role' => 'required|in:site_manager',
            'profile_image' => 'nullable|image|max:512',
            'curator_registration_type' => Rule::when(
                $request->input('role') === 'curator',
                ['required', Rule::in(['existing_landmark'])],
                'nullable|string'
            ),
            'landmark_code' => [
                Rule::requiredIf(fn () => $request->input('role') === 'curator'
                    && $request->input('curator_registration_type') === 'existing_landmark'),
                'nullable',
                'string',
                'max:120',
            ],
        ];

        $request->validate($rules);

        $email = $request->email;
        $password = $request->password;
        $firstName = trim($request->first_name);
        $lastName = trim($request->last_name);
        $name = trim($firstName . ' ' . $lastName);
        $role = $request->role;
        $curatorRegistrationType = $role === 'curator'
            ? (string) $request->input('curator_registration_type')
            : '';
        $landmarkCode = null;
        $assignedLandmarkId = null;
        $approvalStatus = 'approved';
        $requiresApproval = false;
        $profileImageBase64 = null;
        $profileImageMime = null;

        if ($role === 'curator') {
            $requiresApproval = true;
            $approvalStatus = 'pending';

            if ($curatorRegistrationType === 'existing_landmark') {
                $landmarkCode = trim((string) $request->input('landmark_code', ''));
                if ($landmarkCode === '') {
                    return back()->withErrors([
                        'landmark_code' => 'Landmark code is required.',
                    ])->withInput();
                }

                $qrDocs = $this->firebase->firestore()->collection('qr_codes')
                    ->where('code', '==', $landmarkCode)
                    ->limit(1)
                    ->documents();

                foreach ($qrDocs as $qrDoc) {
                    if (! $qrDoc->exists()) {
                        continue;
                    }
                    $qrData = $qrDoc->data();
                    if ($qrData['is_landmark_join_code'] ?? false) {
                        continue;
                    }
                    $assignedLandmarkId = $qrData['landmark_id'] ?? null;
                    break;
                }

                if (! $assignedLandmarkId) {
                    return back()->withErrors([
                        'landmark_code' => 'Invalid landmark code. Please check the code from your Site Manager.',
                    ])->withInput();
                }

                $landmarkSnap = $this->firebase->firestore()
                    ->collection('landmarks')
                    ->document((string) $assignedLandmarkId)
                    ->snapshot();
                $landmarkActivation = $landmarkSnap->exists()
                    ? strtolower((string) ($landmarkSnap->data()['activation_status'] ?? 'active'))
                    : '';

                if (! $landmarkSnap->exists() || in_array($landmarkActivation, ['pending', 'rejected'], true)) {
                    return back()->withErrors([
                        'landmark_code' => 'This landmark is not yet active. Ask your Site Manager when administrator approval is complete.',
                    ])->withInput();
                }
            }
        }

        if ($role === 'site_manager') {
            $requiresApproval = true;
            $approvalStatus = 'pending';
        }

        if ($role === 'curator' && $request->hasFile('profile_image')) {
            $imageFile = $request->file('profile_image');
            $profileImageBase64 = base64_encode(file_get_contents($imageFile->getRealPath()));
            $profileImageMime = $imageFile->getMimeType();
        }

        try {
            $user = $this->firebase->createUser($email, $password, $name);
            $uid = $user->uid;

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
            ];

            if ($role === 'curator') {
                $userData['curator_registration_type'] = $curatorRegistrationType;

                if ($curatorRegistrationType === 'existing_landmark') {
                    $userData['assigned_landmark_id'] = $assignedLandmarkId;
                    $userData['landmark_code'] = $landmarkCode;
                    $userData['pending_landmark_id'] = null;
                }
            }

            if ($profileImageBase64) {
                $userData['profile_image_base64'] = $profileImageBase64;
                $userData['profile_image_mime'] = $profileImageMime;
            }

            $this->firebase->firestore()
                ->collection('users')
                ->document($uid)
                ->set($userData);

            if ($requiresApproval && $role === 'site_manager') {
                $successMessage = 'Registration submitted successfully! Your account is pending admin approval.';
            } elseif ($requiresApproval && $role === 'curator') {
                $successMessage = 'Registration submitted! Your account is pending approval from your Site Manager.';
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
