<?php

namespace App\Tests\Unit\Controller;

use App\Controller\StatusController;
use App\Enum\Status;
use PHPUnit\Framework\TestCase;

class SimpleControllerTest extends TestCase
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
        $this->assertEquals('draft', Status::DRAFT->value);
        $this->assertEquals('published', Status::PUBLISHED->value);
        $this->assertEquals('archived', Status::ARCHIVED->value);
    }

    public function testStatusEnumCases(): void
    {
        $cases = Status::cases();
        $this->assertCount(3, $cases);
        $this->assertContains(Status::DRAFT, $cases);
        $this->assertContains(Status::PUBLISHED, $cases);
        $this->assertContains(Status::ARCHIVED, $cases);
    }

    public function testStatusEnumFrom(): void
    {
        $this->assertEquals(Status::DRAFT, Status::from('draft'));
        $this->assertEquals(Status::PUBLISHED, Status::from('published'));
        $this->assertEquals(Status::ARCHIVED, Status::from('archived'));
    }

    public function testStatusEnumTryFrom(): void
    {
        $this->assertEquals(Status::DRAFT, Status::tryFrom('draft'));
        $this->assertEquals(Status::PUBLISHED, Status::tryFrom('published'));
        $this->assertEquals(Status::ARCHIVED, Status::tryFrom('archived'));
        $this->assertNull(Status::tryFrom('invalid'));
    }

    public function testStatusEnumName(): void
    {
        $this->assertEquals('DRAFT', Status::DRAFT->name);
        $this->assertEquals('PUBLISHED', Status::PUBLISHED->name);
        $this->assertEquals('ARCHIVED', Status::ARCHIVED->name);
    }
}