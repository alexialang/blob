<?php

namespace App\Tests\Unit\Controller;

use App\Controller\UserController;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserControllerTest extends TestCase
{
    private UserService $userService;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->userService = $this->createMock(UserService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testUserControllerCanBeInstantiated(): void
    {
        $controller = new UserController($this->userService, $this->logger);
        $this->assertInstanceOf(UserController::class, $controller);
    }

    public function testUserControllerHasMethods(): void
    {
        $controller = new UserController($this->userService, $this->logger);
        $this->assertTrue(method_exists($controller, 'adminList'));
        $this->assertTrue(method_exists($controller, 'create'));
        $this->assertTrue(method_exists($controller, 'profile'));
        $this->assertTrue(method_exists($controller, 'updateProfile'));
        $this->assertTrue(method_exists($controller, 'statistics'));
        $this->assertTrue(method_exists($controller, 'statisticsById'));
        $this->assertTrue(method_exists($controller, 'show'));
        $this->assertTrue(method_exists($controller, 'update'));
        $this->assertTrue(method_exists($controller, 'anonymize'));
        $this->assertTrue(method_exists($controller, 'confirmAccount'));
        $this->assertTrue(method_exists($controller, 'gameHistory'));
    }
}
