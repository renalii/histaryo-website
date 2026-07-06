<?php

namespace Tests\Feature;

use App\Services\FirebaseService;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LogoutRedirectTest extends TestCase
{
    #[DataProvider('protectedPageProvider')]
    public function test_logged_out_users_are_redirected_to_clean_login_url(string $protectedUrl): void
    {
        $response = $this->get($protectedUrl);

        $response->assertRedirect(route('login'));
        $this->assertStringNotContainsString('redirect=', $response->headers->get('Location'));
    }

    #[DataProvider('roleProvider')]
    public function test_logout_clears_session_and_redirects_to_clean_login_url(string $role): void
    {
        $firebase = Mockery::mock(FirebaseService::class);
        $firebase->shouldReceive('firestore')->andThrow(new \RuntimeException('Firebase unavailable'));
        $this->app->instance(FirebaseService::class, $firebase);

        $response = $this->withSession([
            'uid' => 'admin-1',
            'role' => $role,
            'email' => $role.'@example.com',
            'login_redirect' => url('/admin'),
        ])->get('/logout');

        $response->assertRedirect(route('login'));
        $this->assertStringNotContainsString('redirect=', $response->headers->get('Location'));
        $this->assertFalse(session()->has('uid'));
        $this->assertFalse(session()->has('role'));
        $this->assertFalse(session()->has('email'));
        $this->assertFalse(session()->has('login_redirect'));
    }

    public static function protectedPageProvider(): array
    {
        return [
            'super admin' => ['/admin/users'],
            'site manager' => ['/sitemanager/curators'],
            'curator' => ['/curators/dashboard'],
        ];
    }

    public static function roleProvider(): array
    {
        return [
            'super admin' => ['admin'],
            'site manager' => ['site_manager'],
            'curator' => ['curator'],
        ];
    }
}
