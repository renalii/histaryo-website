<?php

namespace Tests\Feature;

use App\Services\FirebaseService;
use ReflectionProperty;
use Tests\TestCase;

class LoginPagePerformanceTest extends TestCase
{
    public function test_login_page_does_not_initialize_firebase_clients(): void
    {
        $this->get('/login')->assertOk();

        $firebase = $this->app->make(FirebaseService::class);

        $this->assertNull((new ReflectionProperty($firebase, 'factory'))->getValue($firebase));
        $this->assertNull((new ReflectionProperty($firebase, 'auth'))->getValue($firebase));
        $this->assertNull((new ReflectionProperty($firebase, 'firestore'))->getValue($firebase));
    }
}
