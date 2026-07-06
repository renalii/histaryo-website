<?php

namespace Tests\Unit;

use App\Support\TemporaryPassword;
use PHPUnit\Framework\TestCase;

class TemporaryPasswordTest extends TestCase
{
    public function test_it_generates_a_secure_sixteen_character_password(): void
    {
        $password = TemporaryPassword::generate();

        $this->assertSame(16, strlen($password));
        $this->assertMatchesRegularExpression('/[A-Z]/', $password);
        $this->assertMatchesRegularExpression('/[a-z]/', $password);
        $this->assertMatchesRegularExpression('/[0-9]/', $password);
        $this->assertMatchesRegularExpression('/[!@#$%&*?]/', $password);
    }

    public function test_it_rejects_lengths_below_the_secure_minimum(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TemporaryPassword::generate(11);
    }
}
