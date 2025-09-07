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

    public function testGetCompanyStatisticsCallsServices(): void
    {
        $company = $this->createMock(Company::class);
        $company->method('getId')->willReturn(123);

        $expectedData = [
            'teamScores' => ['some' => 'data'],
            'groupScores' => ['other' => 'data']
        ];

        // Test que le cache est supprimé
        $this->cache->expects($this->once())
            ->method('delete')
            ->with('company_statistics_123');

        // Test que le service est appelé
        $this->globalStatisticsService->expects($this->once())
            ->method('getCompanyStatistics')
            ->with(123)
            ->willReturn($expectedData);

        // Appel de la méthode
        $this->controller->getCompanyStatistics($company);
    }

    public function testGetCompanyStatisticsMethodExists(): void
    {
        $this->assertTrue(method_exists($this->controller, 'getCompanyStatistics'));
    }
}
