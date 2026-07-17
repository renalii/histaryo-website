<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SiteManagerCuratorRoutesTest extends TestCase
{
    public function test_curator_actions_expose_delete_deactivate_and_activate(): void
    {
        $deleteRoute = Route::getRoutes()->getByName('sitemanager.curators.destroy');
        $deactivateRoute = Route::getRoutes()->getByName('sitemanager.curators.deactivate');
        $activateRoute = Route::getRoutes()->getByName('sitemanager.curators.activate');

        $this->assertNotNull($deleteRoute);
        $this->assertContains('DELETE', $deleteRoute->methods());
        $this->assertNotNull($deactivateRoute);
        $this->assertContains('POST', $deactivateRoute->methods());
        $this->assertNotNull($activateRoute);
        $this->assertContains('POST', $activateRoute->methods());
    }
}
