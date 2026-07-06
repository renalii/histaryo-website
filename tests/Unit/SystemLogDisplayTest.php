<?php

namespace Tests\Unit;

use App\Support\SystemLogDisplay;
use PHPUnit\Framework\TestCase;

class SystemLogDisplayTest extends TestCase
{
    public function test_it_adds_role_context_to_generic_login_logs(): void
    {
        $this->assertSame('Admin logged in', SystemLogDisplay::formatAction('Logged in', [], 'admin'));
        $this->assertSame('Site Manager logged out', SystemLogDisplay::formatAction('Logged out', [], 'site_manager'));
        $this->assertSame('Curator logged in', SystemLogDisplay::formatAction('Logged in', [], 'curator'));
    }

    public function test_it_adds_target_context_to_existing_action_metadata(): void
    {
        $this->assertSame(
            'Site Manager updated curator account: rheaolivo8@gmail.com',
            SystemLogDisplay::formatAction('Site Manager updated curator account', [
                'curator_email' => 'rheaolivo8@gmail.com',
            ], 'site_manager')
        );

        $this->assertSame(
            'Admin approved landmark: Magellan\'s Cross',
            SystemLogDisplay::formatAction('Landmark approved by admin', [
                'landmark_name' => 'Magellan\'s Cross',
            ], 'admin')
        );
    }

    public function test_it_formats_site_manager_landmark_activity_with_names(): void
    {
        $this->assertSame(
            'Site Manager created landmark: Simala Shrine',
            SystemLogDisplay::formatAction('Created landmark', [
                'landmark_name' => 'Simala Shrine',
            ])
        );

        $this->assertSame(
            'Site Manager updated landmark: Osmeña Peak',
            SystemLogDisplay::formatAction('Updated landmark', [
                'landmark_name' => 'Osmeña Peak',
            ])
        );

        $this->assertSame(
            'Site Manager deleted landmark: Fort San Pedro',
            SystemLogDisplay::formatAction('Site Manager deleted landmark successfully.', [
                'landmark_name' => 'Fort San Pedro',
            ])
        );
    }

    public function test_it_infers_site_manager_role_for_landmark_activity(): void
    {
        $this->assertSame(
            'Site Manager',
            SystemLogDisplay::roleLabel(SystemLogDisplay::roleForLog(null, 'Site Manager deleted landmark: Fort San Pedro'))
        );
    }
}
