<?php

namespace Tests\Feature;

use Tests\TestCase;

class MfaAccessTest extends TestCase
{
    public function test_site_manager_cannot_access_panel_without_mfa(): void
    {
        $response = $this->withSession([
            'uid' => 'site-manager-1',
            'role' => 'site_manager',
        ])->get('/sitemanager');

        $response->assertRedirect(route('login'));
        $this->assertGuestSession();
    }

    public function test_curator_cannot_access_panel_without_mfa(): void
    {
        $response = $this->withSession([
            'uid' => 'curator-1',
            'role' => 'curator',
        ])->get('/curators/dashboard');

        $response->assertRedirect(route('login'));
        $this->assertGuestSession();
    }

    public function test_expired_mfa_challenge_cannot_be_displayed(): void
    {
        $response = $this->withSession([
            'mfa_pending' => [
                'uid' => 'curator-1',
                'email' => 'curator@example.com',
                'expires_at' => time() - 1,
            ],
        ])->get(route('login.mfa'));

        $response->assertRedirect(route('login'));
    }

    public function test_mfa_settings_cannot_be_opened_before_mfa_completion(): void
    {
        $response = $this->withSession([
            'uid' => 'curator-1',
            'role' => 'curator',
        ])->get(route('mfa.settings'));

        $response->assertRedirect(route('login'));
    }

    private function assertGuestSession(): void
    {
        $this->assertFalse(session()->has('uid'));
        $this->assertFalse(session()->has('mfa_verified'));
    }
}
