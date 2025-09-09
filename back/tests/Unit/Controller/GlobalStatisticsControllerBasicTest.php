<?php

namespace App\Tests\Unit\Controller;

use App\Controller\GlobalStatisticsController;
use App\Entity\Company;
use App\Service\GlobalStatisticsService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class GlobalStatisticsControllerBasicTest extends TestCase
{
    private GlobalStatisticsController $controller;
    private GlobalStatisticsService $globalStatisticsService;
    private CacheInterface $cache;

    protected function setUp(): void
    {
        $this->globalStatisticsService = $this->createMock(GlobalStatisticsService::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->controller = new GlobalStatisticsController(
            $this->globalStatisticsService,
            $this->cache
        );
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(GlobalStatisticsController::class, $this->controller);
    }


    public function testGetCompanyStatisticsMethodExists(): void
    {
        $this->assertTrue(method_exists($this->controller, 'getCompanyStatistics'));
    }
}
