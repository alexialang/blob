<?php

namespace App\Tests\Unit\Controller;

use App\Controller\GroupController;
use App\Entity\Group;
use App\Service\GroupService;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;

class GroupControllerFinalTest extends TestCase
{
    private GroupController $controller;
    private GroupService $groupService;
    private UserService $userService;

    protected function setUp(): void
    {
        $this->groupService = $this->createMock(GroupService::class);
        $this->userService = $this->createMock(UserService::class);
        $this->controller = new GroupController($this->groupService, $this->userService);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(GroupController::class, $this->controller);
    }

    public function testControllerHasAllMethods(): void
    {
        $methods = ['index', 'create', 'delete', 'addUser', 'removeUser'];

        foreach ($methods as $method) {
            $this->assertTrue(method_exists($this->controller, $method));
        }
    }
}
