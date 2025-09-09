<?php

namespace App\Tests\Unit\Controller;

use App\Controller\StatusController;
use PHPUnit\Framework\TestCase;

class StatusControllerTest extends TestCase
{
    public function testStatusControllerCanBeInstantiated(): void
    {
        $controller = new StatusController();
        $this->assertInstanceOf(StatusController::class, $controller);
    }

    public function testStatusControllerHasListMethod(): void
    {
        $controller = new StatusController();
        $this->assertTrue(method_exists($controller, 'list'));
    }
}