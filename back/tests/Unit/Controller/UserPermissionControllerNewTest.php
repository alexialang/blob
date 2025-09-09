<?php

namespace App\Tests\Unit\Controller;

use App\Controller\UserPermissionController;
use App\Service\UserPermissionService;
use App\Service\UserService;
use App\Repository\UserRepository;
use PHPUnit\Framework\TestCase;

class UserPermissionControllerNewTest extends TestCase
{
    private UserPermissionService $userPermissionService;
    private UserService $userService;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->userPermissionService = $this->createMock(UserPermissionService::class);
        $this->userService = $this->createMock(UserService::class);
        $this->userRepository = $this->createMock(UserRepository::class);
    }

    public function testUserPermissionControllerCanBeInstantiated(): void
    {
        $controller = new UserPermissionController($this->userPermissionService, $this->userService, $this->userRepository);
        $this->assertInstanceOf(UserPermissionController::class, $controller);
    }

    public function testUserPermissionControllerHasMethods(): void
    {
        $controller = new UserPermissionController($this->userPermissionService, $this->userService, $this->userRepository);
        $this->assertTrue(method_exists($controller, 'index'));
        $this->assertTrue(method_exists($controller, 'create'));
        $this->assertTrue(method_exists($controller, 'show'));
        $this->assertTrue(method_exists($controller, 'update'));
        $this->assertTrue(method_exists($controller, 'delete'));
        $this->assertTrue(method_exists($controller, 'updateUserRoles'));
    }
}
