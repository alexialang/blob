<?php

namespace App\Tests\Unit\Controller;

use App\Controller\BadgeController;
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

    public function testControllerHasRequiredMethods(): void
    {
        $this->assertTrue(method_exists($this->controller, 'index'));
        $this->assertTrue(method_exists($this->controller, 'show'));
    }
}
