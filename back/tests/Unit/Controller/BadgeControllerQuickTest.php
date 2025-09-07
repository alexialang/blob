<?php

namespace App\Tests\Unit\Controller;

use App\Controller\BadgeController;
use App\Entity\Badge;
use App\Service\BadgeService;
use PHPUnit\Framework\TestCase;

class BadgeControllerQuickTest extends TestCase
{
    private BadgeController $controller;
    private BadgeService $badgeService;

    protected function setUp(): void
    {
        $this->badgeService = $this->createMock(BadgeService::class);
        $this->controller = new BadgeController($this->badgeService);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(BadgeController::class, $this->controller);
    }

    public function testIndexCallsService(): void
    {
        $badges = [
            $this->createMock(Badge::class),
            $this->createMock(Badge::class)
        ];

        $this->badgeService->expects($this->once())
            ->method('list')
            ->willReturn($badges);

        // Test que la méthode existe et peut être appelée
        $this->controller->index();
    }

    public function testShowMethodExists(): void
    {
        $badge = $this->createMock(Badge::class);
        
        // Test que la méthode existe
        $this->assertTrue(method_exists($this->controller, 'show'));
        
        // Appel de la méthode
        $this->controller->show($badge);
    }

    public function testControllerHasRequiredMethods(): void
    {
        $this->assertTrue(method_exists($this->controller, 'index'));
        $this->assertTrue(method_exists($this->controller, 'show'));
    }
}
