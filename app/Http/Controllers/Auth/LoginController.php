<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirebaseService;
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

            
            Session::put('uid', $uid);
            Session::put('role', $role);
            Session::put('email', $email);
            if ($name) {
                Session::put('name', $name);
            }

            
            $this->firebase->firestore()->collection('logs')->add([
                'email' => $email,
                'action' => 'Logged in',
                'timestamp' => now()->toISOString(),
            ]);

            
            if ($role === 'admin') {
                return redirect()->route('admin.dashboard')->with('success', 'Welcome Admin!');
            } elseif ($role === 'curator') {
                return redirect()->route('curators.dashboard')->with('success', 'Welcome Curator!');
            } else {
                return back()->withErrors(['error' => 'Unauthorized role.']);
            }

        } catch (UserNotFound | InvalidPassword $e) {
            return back()->withErrors(['error' => 'Invalid email or password.']);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Login failed. ' . $e->getMessage()]);
        }
    }

    public function logout()
    {
        $email = Session::get('email');

        if ($email) {
            $this->firebase->firestore()->collection('logs')->add([
                'email' => $email,
                'action' => 'Logged out',
                'timestamp' => now()->toISOString(),
            ]);
        }

        Session::flush();
        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }
}
