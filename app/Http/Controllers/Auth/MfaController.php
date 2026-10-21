<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use App\Services\MfaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MfaController extends Controller
{
    public function __construct(
        private MfaService $mfa,
        private FirebaseService $firebase,
    ) {}

    public function show(): mixed
    {
        [$uid, $role, $email] = $this->identity();
        if (! $this->authorized($uid, $role)) {
            return redirect()->route('login');
        }

        $settings = $this->mfa->settings($uid, $role);
        $secret = null;
        if (filled($settings->totp_secret)) {
            try {
                $secret = Crypt::decryptString($settings->totp_secret);
            } catch (\Throwable) {
                $secret = null;
            }
        }
        $secret ??= $this->newTotpSecret();
        $uri = $this->totpUri($secret, $email, 'HistARyo');

        return view('auth.mfa-settings', [
            'enabled' => filled($settings->totp_secret),
            'secret' => $secret,
            'uri' => $uri,
            'qrCode' => (string) QrCode::format('svg')->size(220)->margin(1)->generate($uri),
            'methods' => $this->mfa->methods($uid, $role),
        ]);
    }

    public function enableAuthenticator(Request $request): RedirectResponse
    {
        [$uid, $role, $email] = $this->identity();
        $request->validate([
            'password' => ['required', 'string'],
            'secret' => ['required', 'string', 'regex:/^[A-Z2-7]{16,64}$/'],
            'code' => ['required', 'digits:6'],
        ]);
        if (! $this->authorized($uid, $role) || ! $this->reauthenticate($email, $request->string('password')->toString())) {
            return back()->withErrors(['password' => 'Reauthentication failed.']);
        }
        if (! $this->totpValid($request->string('secret')->toString(), $request->string('code')->toString())) {
            return back()->withErrors(['code' => 'The authenticator code is invalid.']);
        }

        $codes = $this->mfa->generateRecoveryCodes();
        $this->mfa->saveTotp(
            $uid,
            $role,
            Crypt::encryptString($request->string('secret')->toString()),
            array_map(fn (string $code): string => Hash::make($code), $codes),
        );
        $this->notify($email, 'Authenticator app enabled on your HistARyo account.');

        return redirect()->route('mfa.settings')->with('recovery_codes', $codes);
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        [$uid, $role, $email] = $this->identity();
        $request->validate(['password' => ['required', 'string']]);
        if (! $this->authorized($uid, $role) || ! $this->reauthenticate($email, $request->string('password')->toString())) {
            return back()->withErrors(['password' => 'Reauthentication failed.']);
        }
        $secret = $this->mfa->encryptedTotpSecret($uid, $role);
        if (! $secret) {
            return back()->withErrors(['password' => 'Enable an authenticator app first.']);
        }

        $codes = $this->mfa->generateRecoveryCodes();
        $settings = $this->mfa->settings($uid, $role);
        $this->mfa->saveTotp($uid, $role, $secret, array_map(fn (string $code): string => Hash::make($code), $codes));
        DB::table('mfa_settings')->where('uid', $uid)->update(['last_recovery_regenerated_at' => now()]);
        $this->notify($email, 'Your HistARyo recovery codes were regenerated.');

        return redirect()->route('mfa.settings')->with('recovery_codes', $codes);
    }

    public function disableAuthenticator(Request $request): RedirectResponse
    {
        [$uid, $role, $email] = $this->identity();
        $request->validate(['password' => ['required', 'string']]);
        if (! $this->authorized($uid, $role) || ! $this->reauthenticate($email, $request->string('password')->toString())) {
            return back()->withErrors(['password' => 'Reauthentication failed.']);
        }
        $this->mfa->disableTotp($uid);
        $this->notify($email, 'Authenticator app MFA was disabled on your HistARyo account. Email OTP remains required.');

        return redirect()->route('mfa.settings')->with('status', 'Authenticator app disabled. Email OTP remains enabled.');
    }

    private function identity(): array
    {
        return [
            (string) Session::get('uid', ''),
            (string) Session::get('role', ''),
            (string) Session::get('email', ''),
        ];
    }

    private function authorized(string $uid, string $role): bool
    {
        return $uid !== '' && Session::get('mfa_verified') === true
            && in_array($role, ['curator', 'site_manager'], true);
    }

    private function reauthenticate(string $email, string $password): bool
    {
        try {
            $this->firebase->signInWithEmailAndPassword($email, $password);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function notify(string $email, string $message): void
    {
        Mail::raw($message, fn ($mail) => $mail->to($email)->subject('HistARyo MFA security alert'));
    }

    private function newTotpSecret(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $secret;
    }

    private function totpUri(string $secret, string $email, string $issuer): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$email)
            .'?secret='.rawurlencode($secret).'&issuer='.rawurlencode($issuer).'&digits=6&period=30';
    }

    private function totpValid(string $secret, string $code): bool
    {
        if (! preg_match('/^\d{6}$/', $code)) return false;
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper($secret);
        $bits = 0;
        $value = 0;
        $binary = '';
        for ($i = 0; $i < strlen($secret); $i++) {
            $position = strpos($alphabet, $secret[$i]);
            if ($position === false) return false;
            $value = ($value << 5) | $position;
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $binary .= chr(($value >> $bits) & 255);
            }
        }

        $counter = intdiv(time(), 30);
        for ($offset = -1; $offset <= 1; $offset++) {
            $current = $counter + $offset;
            $payload = pack('N2', ($current >> 32) & 0xffffffff, $current & 0xffffffff);
            $hash = hash_hmac('sha1', $payload, $binary, true);
            $position = ord($hash[19]) & 15;
            $otp = ((ord($hash[$position]) & 127) << 24)
                | (ord($hash[$position + 1]) << 16)
                | (ord($hash[$position + 2]) << 8)
                | ord($hash[$position + 3]);
            if (hash_equals(str_pad((string) ($otp % 1000000), 6, '0', STR_PAD_LEFT), $code)) return true;
        }
        return false;
    }
}
