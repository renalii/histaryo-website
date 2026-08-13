<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminPanelSessionTest extends TestCase
{
    public function test_guest_is_redirected_from_admin_users_to_login(): void
    {
        $response = $this->get('/admin/users');

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_is_redirected_from_admin_users_to_login(): void
    {
        $response = $this->withSession([
            'uid' => 'site-manager-1',
            'role' => 'curator',
        ])->get('/admin/users');

        $response->assertRedirect(route('login'));
    }

}
