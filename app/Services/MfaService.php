<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MfaService
{
    public const EMAIL = 'email';
    public const TOTP = 'authenticator';
    public const RECOVERY = 'recovery';

    public function settings(string $uid, string $role): object
    {
        DB::table('mfa_settings')->insertOrIgnore([
            'uid' => $uid,
            'role' => $role,
            'email_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return DB::table('mfa_settings')->where('uid', $uid)->first();
    }

    public function methods(string $uid, string $role): array
    {
        $settings = $this->settings($uid, $role);
        $methods = [self::EMAIL];
        if (filled($settings->totp_secret)) {
            $methods[] = self::TOTP;
        }
        if ($this->recoveryHashes($settings) !== []) {
            $methods[] = self::RECOVERY;
        }

        return $methods;
    }

    public function issueEmailChallenge(string $uid, string $role, string $sessionKey, string $ip): string
    {
        $code = (string) random_int(100000, 999999);
        $id = DB::table('mfa_challenges')->insertGetId([
            'uid' => $uid,
            'role' => $role,
            'session_hash' => hash('sha256', $sessionKey),
            'otp_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(5),
            'last_sent_at' => now(),
            'ip_address' => $ip,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (string) $id.'|'.$code;
    }

    public function invalidateChallenge(string $id, string $sessionKey): void
    {
        DB::table('mfa_challenges')
            ->where('id', $id)
            ->where('session_hash', hash('sha256', $sessionKey))
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now(), 'updated_at' => now()]);
    }

    public function verifyEmailChallenge(string $id, string $sessionKey, string $code): bool
    {
        $challenge = DB::table('mfa_challenges')
            ->where('id', $id)
            ->where('session_hash', hash('sha256', $sessionKey))
            ->whereNull('consumed_at')
            ->first();

        if (! $challenge || now()->greaterThan($challenge->expires_at) || (int) $challenge->attempts >= 5) return false;

        DB::table('mfa_challenges')->where('id', $id)->increment('attempts');
        if (! Hash::check($code, $challenge->otp_hash)) return false;

        return DB::table('mfa_challenges')
            ->where('id', $id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now(), 'updated_at' => now()]) === 1;
    }

    public function encryptedTotpSecret(string $uid, string $role): ?string
    {
        return $this->settings($uid, $role)->totp_secret ?: null;
    }

    public function verifyTotp(string $uid, string $role, string $code): bool
    {
        $encrypted = $this->encryptedTotpSecret($uid, $role);
        if (! $encrypted) return false;
        try {
            $secret = Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return false;
        }
        if (! preg_match('/^\d{6}$/', $code)) return false;
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = 0; $value = 0; $binary = '';
        for ($i = 0; $i < strlen($secret); $i++) {
            $position = strpos($alphabet, strtoupper($secret[$i]));
            if ($position === false) return false;
            $value = ($value << 5) | $position; $bits += 5;
            if ($bits >= 8) { $bits -= 8; $binary .= chr(($value >> $bits) & 255); }
        }
        $counter = intdiv(time(), 30);
        for ($offset = -1; $offset <= 1; $offset++) {
            $current = $counter + $offset;
            $payload = pack('N2', ($current >> 32) & 0xffffffff, $current & 0xffffffff);
            $digest = hash_hmac('sha1', $payload, $binary, true);
            $position = ord($digest[19]) & 15;
            $otp = ((ord($digest[$position]) & 127) << 24) | (ord($digest[$position + 1]) << 16)
                | (ord($digest[$position + 2]) << 8) | ord($digest[$position + 3]);
            if (hash_equals(str_pad((string) ($otp % 1000000), 6, '0', STR_PAD_LEFT), $code)) {
                return DB::transaction(function () use ($uid, $counter, $offset): bool {
                    $settings = DB::table('mfa_settings')->where('uid', $uid)->lockForUpdate()->first();
                    $matchedCounter = $counter + $offset;
                    if (! $settings || ($settings->last_totp_counter !== null && (int) $settings->last_totp_counter >= $matchedCounter)) {
                        return false;
                    }
                    DB::table('mfa_settings')->where('uid', $uid)->update([
                        'last_totp_counter' => $matchedCounter,
                        'updated_at' => now(),
                    ]);
                    return true;
                });
            }
        }
        return false;
    }

    public function recoveryHashes(object $settings): array
    {
        $hashes = json_decode((string) ($settings->recovery_code_hashes ?? ''), true);
        return is_array($hashes) ? array_values(array_filter($hashes, 'is_string')) : [];
    }

    public function saveTotp(string $uid, string $role, string $encryptedSecret, array $recoveryHashes): void
    {
        DB::table('mfa_settings')->where('uid', $uid)->update([
            'role' => $role,
            'email_enabled' => true,
            'totp_secret' => $encryptedSecret,
            'recovery_code_hashes' => json_encode(array_values($recoveryHashes), JSON_THROW_ON_ERROR),
            'totp_enabled_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function consumeRecoveryCode(string $uid, string $role, string $code): bool
    {
        return DB::transaction(function () use ($uid, $role, $code): bool {
            $settings = DB::table('mfa_settings')->where('uid', $uid)->where('role', $role)->lockForUpdate()->first();
            if (! $settings) return false;
            $hashes = $this->recoveryHashes($settings);
            foreach ($hashes as $index => $hash) {
                if (! Hash::check(strtoupper(trim($code)), $hash)) continue;
                unset($hashes[$index]);
                DB::table('mfa_settings')->where('uid', $uid)->update([
                    'recovery_code_hashes' => json_encode(array_values($hashes), JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                ]);
                return true;
            }
            return false;
        });
    }

    public function disableTotp(string $uid): void
    {
        DB::table('mfa_settings')->where('uid', $uid)->update([
            'totp_secret' => null,
            'recovery_code_hashes' => null,
            'totp_enabled_at' => null,
            'updated_at' => now(),
        ]);
    }

    public function generateRecoveryCodes(): array
    {
        return array_map(
            fn (): string => strtoupper(Str::random(4).'-'.Str::random(4)),
            range(1, 10)
        );
    }
}
