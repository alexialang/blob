<?php

namespace App\Tests\Unit\Controller;

use App\Controller\PasswordResetController;
use PHPUnit\Framework\TestCase;

class PasswordResetControllerNewTest extends TestCase
{
    public function testPasswordResetControllerCanBeInstantiated(): void
    {
        $resetService = $this->createMock(\App\Service\UserPasswordResetService::class);
        $userService = $this->createMock(\App\Service\UserService::class);

        $controller = new PasswordResetController($resetService, $userService);
        $this->assertInstanceOf(PasswordResetController::class, $controller);
    }

    public function testPasswordResetControllerHasMethods(): void
    {
        $resetService = $this->createMock(\App\Service\UserPasswordResetService::class);
        $userService = $this->createMock(\App\Service\UserService::class);

        $controller = new PasswordResetController($resetService, $userService);
        $this->assertTrue(method_exists($controller, 'forgotPassword'));
        $this->assertTrue(method_exists($controller, 'resetPassword'));
    }
}
