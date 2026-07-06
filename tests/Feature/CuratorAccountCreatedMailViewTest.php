<?php

namespace Tests\Feature;

use Tests\TestCase;

class CuratorAccountCreatedMailViewTest extends TestCase
{
    public function test_landmark_assignment_hides_trailing_landmark_code(): void
    {
        $html = view('emails.curator-account-created', [
            'firstName' => 'Rhea',
            'lastName' => 'Olivo',
            'email' => 'rhea@example.com',
            'plainPassword' => 'secret',
            'landmarkLabel' => 'Magellans Cross (LM2Q98B8)',
            'changePasswordUrl' => 'http://example.test',
        ])->render();

        $this->assertStringContainsString('Magellans Cross', $html);
        $this->assertStringNotContainsString('LM2Q98B8', $html);
    }
}
