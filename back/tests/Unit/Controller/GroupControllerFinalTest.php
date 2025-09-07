<?php

namespace App\Tests\Unit\Controller;

use App\Controller\GroupController;
use App\Entity\Group;
use App\Service\GroupService;
use PHPUnit\Framework\TestCase;

class GroupControllerFinalTest extends TestCase
{
    private GroupController $controller;
    private GroupService $groupService;

    protected function setUp(): void
    {
        $this->groupService = $this->createMock(GroupService::class);
        $this->controller = new GroupController($this->groupService);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(GroupController::class, $this->controller);
    }

    public function testIndexCallsService(): void
    {
        $groups = [
            $this->createMock(Group::class),
            $this->createMock(Group::class)
        ];

        $this->groupService->expects($this->once())
            ->method('list')
            ->willReturn($groups);

        $this->controller->index();
    }

    public function testShowMethodExists(): void
    {
        $group = $this->createMock(Group::class);
        
        $this->assertTrue(method_exists($this->controller, 'show'));
        $this->controller->show($group);
    }

    public function testControllerHasAllMethods(): void
    {
        $methods = ['index', 'show', 'create', 'update', 'delete'];
        
        foreach ($methods as $method) {
            $this->assertTrue(method_exists($this->controller, $method));
        }
    }
}
