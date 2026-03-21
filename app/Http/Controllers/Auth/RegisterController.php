<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirebaseService;
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
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
            'name' => 'required|string',
            'role' => 'required|in:admin,curator',
            'profile_image' => 'nullable|image|max:512',
        ]);

        $email = $request->email;
        $password = $request->password;
        $name = $request->name;
        $role = $request->role;
        $profileImageBase64 = null;
        $profileImageMime = null;

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
                'name' => $name,
                'role' => $role,
                'created_at' => now()->toDateTimeString(),
            ];

            if ($profileImageBase64) {
                $userData['profile_image_base64'] = $profileImageBase64;
                $userData['profile_image_mime'] = $profileImageMime;
            }

            $this->firebase->firestore()
                ->collection('users')
                ->document($uid)
                ->set($userData);

            return redirect()->route('login')->with('success', 'Registration successful! Please log in.');

        } catch (EmailExists $e) {
            return back()->withErrors(['error' => 'The email is already registered. Please use a different one.'])->withInput();
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Registration failed. ' . $e->getMessage()])->withInput();
        }
    }
}
