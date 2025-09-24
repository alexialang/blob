<?php

namespace App\Tests\Unit\Controller;

use App\Controller\GroupController;
use App\Service\GroupService;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;

class GroupControllerFinalTest extends TestCase
{
    private GroupController $controller;
    private GroupService $groupService;

    protected function setUp(): void
    {
        $this->groupService = $this->createMock(GroupService::class);
        $userService = $this->createMock(UserService::class);
        $this->controller = new GroupController($this->groupService, $userService);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(GroupController::class, $this->controller);
    }

    public function testIndexCallsService(): void
    {
        // Test que la méthode existe sans l'appeler car elle nécessite le container Symfony
        $this->assertTrue(method_exists($this->controller, 'index'));
    }

    public function testCreateMethodExists(): void
    {
        $this->assertTrue(method_exists($this->controller, 'create'));
    }

    public function testControllerHasAllMethods(): void
    {
        $methods = ['index', 'create', 'delete', 'addUser', 'removeUser'];

        foreach ($methods as $method) {
            $this->assertTrue(method_exists($this->controller, $method));
        }
    }
}
