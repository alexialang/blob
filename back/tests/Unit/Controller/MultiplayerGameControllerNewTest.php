<?php

namespace App\Tests\Unit\Controller;

use App\Controller\MultiplayerGameController;
use App\Service\GroupService;
use App\Service\MultiplayerGameService;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;

class MultiplayerGameControllerNewTest extends TestCase
{
    private MultiplayerGameService $multiplayerService;
    private GroupService $groupService;
    private UserService $userService;

    protected function setUp(): void
    {
        $this->multiplayerService = $this->createMock(MultiplayerGameService::class);
        $this->groupService = $this->createMock(GroupService::class);
        $this->userService = $this->createMock(UserService::class);
    }

    public function testMultiplayerGameControllerCanBeInstantiated(): void
    {
        $controller = new MultiplayerGameController($this->multiplayerService, $this->groupService, $this->userService);
        $this->assertInstanceOf(MultiplayerGameController::class, $controller);
    }

    public function testMultiplayerGameControllerHasMethods(): void
    {
        $controller = new MultiplayerGameController($this->multiplayerService, $this->groupService, $this->userService);
        $this->assertTrue(method_exists($controller, 'createRoom'));
        $this->assertTrue(method_exists($controller, 'joinRoom'));
        $this->assertTrue(method_exists($controller, 'leaveRoom'));
        $this->assertTrue(method_exists($controller, 'startGame'));
        $this->assertTrue(method_exists($controller, 'getRoomStatus'));
        $this->assertTrue(method_exists($controller, 'submitAnswer'));
        $this->assertTrue(method_exists($controller, 'getGameStatus'));
        $this->assertTrue(method_exists($controller, 'submitPlayerScores'));
        $this->assertTrue(method_exists($controller, 'getAvailableRooms'));
        $this->assertTrue(method_exists($controller, 'triggerFeedback'));
        $this->assertTrue(method_exists($controller, 'nextQuestion'));
        $this->assertTrue(method_exists($controller, 'sendInvitation'));
        $this->assertTrue(method_exists($controller, 'endGame'));
        $this->assertTrue(method_exists($controller, 'getAvailableUsers'));
        $this->assertTrue(method_exists($controller, 'getCompanyGroups'));
        $this->assertTrue(method_exists($controller, 'getCompanyMembers'));
    }
}
