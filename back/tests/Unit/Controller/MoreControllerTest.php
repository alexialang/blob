<?php

namespace App\Tests\Unit\Controller;

use App\Controller\BadgeController;
use App\Controller\CategoryQuizController;
use App\Controller\GlobalStatisticsController;
use App\Controller\GroupController;
use App\Service\BadgeService;
use App\Service\CategoryQuizService;
use App\Service\GlobalStatisticsService;
use App\Service\GroupService;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class MoreControllerTest extends TestCase
{
    public function testBadgeControllerConstructor(): void
    {
        $badgeService = $this->createMock(BadgeService::class);
        $controller = new BadgeController($badgeService);
        $this->assertInstanceOf(BadgeController::class, $controller);
    }

    public function testCategoryQuizControllerConstructor(): void
    {
        $categoryService = $this->createMock(CategoryQuizService::class);
        $controller = new CategoryQuizController($categoryService);
        $this->assertInstanceOf(CategoryQuizController::class, $controller);
    }

    public function testGlobalStatisticsControllerConstructor(): void
    {
        $statsService = $this->createMock(GlobalStatisticsService::class);
        $cache = $this->createMock(CacheInterface::class);
        $controller = new GlobalStatisticsController($statsService, $cache);
        $this->assertInstanceOf(GlobalStatisticsController::class, $controller);
    }

    public function testGroupControllerConstructor(): void
    {
        $groupService = $this->createMock(GroupService::class);
        $userService = $this->createMock(UserService::class);
        $controller = new GroupController($groupService, $userService);
        $this->assertInstanceOf(GroupController::class, $controller);
    }
}
