<?php

namespace App\Tests\Unit\Controller;

use App\Controller\StatusController;
use App\Enum\Status;
use PHPUnit\Framework\TestCase;

class AdditionalControllerTest extends TestCase
{
    public function testStatusControllerConstructor(): void
    {
        $controller = new StatusController();
        $this->assertInstanceOf(StatusController::class, $controller);
    }

    public function testStatusControllerHasListMethod(): void
    {
        $controller = new StatusController();
        $this->assertTrue(method_exists($controller, 'list'));
    }

    public function testStatusEnumValues(): void
    {
        $this->assertInstanceOf(Status::class, Status::DRAFT);
        $this->assertInstanceOf(Status::class, Status::PUBLISHED);
        $this->assertInstanceOf(Status::class, Status::ARCHIVED);
    }
}



