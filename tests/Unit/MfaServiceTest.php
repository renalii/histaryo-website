<?php

namespace Tests\Unit;

use App\Services\MfaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MfaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_challenge_is_single_use_and_expires(): void
    {
        $service = app(MfaService::class);
        $key = 'server-session-key';
        [$id, $code] = explode('|', $service->issueEmailChallenge('uid-1', 'curator', $key, '127.0.0.1'), 2);

        $this->assertTrue($service->verifyEmailChallenge($id, $key, $code));
        $this->assertFalse($service->verifyEmailChallenge($id, $key, $code));

        [$expiredId, $expiredCode] = explode('|', $service->issueEmailChallenge('uid-1', 'curator', $key, '127.0.0.1'), 2);
        \DB::table('mfa_challenges')->where('id', $expiredId)->update(['expires_at' => now()->subMinute()]);
        $this->assertFalse($service->verifyEmailChallenge($expiredId, $key, $expiredCode));
    }

    public function test_recovery_code_is_consumed(): void
    {
        $service = app(MfaService::class);
        $service->settings('uid-2', 'site_manager');
        $service->saveTotp('uid-2', 'site_manager', Crypt::encryptString('JBSWY3DPEHPK3PXP'), [
            Hash::make('ABCD-1234'),
        ]);

        $this->assertTrue($service->consumeRecoveryCode('uid-2', 'site_manager', 'abcd-1234'));
        $this->assertFalse($service->consumeRecoveryCode('uid-2', 'site_manager', 'abcd-1234'));
    }

    public function test_totp_accepts_current_code(): void
    {
        $service = app(MfaService::class);
        $service->settings('uid-3', 'curator');
        $service->saveTotp('uid-3', 'curator', Crypt::encryptString('JBSWY3DPEHPK3PXP'), []);
        $counter = intdiv(time(), 30);
        $key = $this->base32Decode('JBSWY3DPEHPK3PXP');
        $digest = hash_hmac('sha1', pack('N2', 0, $counter), $key, true);
        $position = ord($digest[19]) & 15;
        $otp = ((ord($digest[$position]) & 127) << 24) | (ord($digest[$position + 1]) << 16)
            | (ord($digest[$position + 2]) << 8) | ord($digest[$position + 3]);

        $this->assertTrue($service->verifyTotp('uid-3', 'curator', str_pad((string) ($otp % 1000000), 6, '0', STR_PAD_LEFT)));
    }

    private function base32Decode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = 0; $value = 0; $binary = '';
        foreach (str_split($secret) as $character) {
            $value = ($value << 5) | strpos($alphabet, $character);
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $binary .= chr(($value >> $bits) & 255);
            }
        }
        return $binary;
    }
}
