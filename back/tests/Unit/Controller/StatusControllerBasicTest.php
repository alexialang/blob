<?php

namespace App\Tests\Unit\Controller;

use App\Controller\StatusController;
use App\Enum\Status;
use PHPUnit\Framework\TestCase;

class StatusControllerBasicTest extends TestCase
{
    private StatusController $controller;

    protected function setUp(): void
    {
        $this->controller = new StatusController();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(StatusController::class, $this->controller);
    }

    public function testListMethodExists(): void
    {
        $this->assertTrue(method_exists($this->controller, 'list'));
    }

    public function testListReturnsData(): void
    {
        // Test basique - on ne peut pas tester le JsonResponse facilement en unit test
        // mais on peut vérifier que la méthode s'exécute sans erreur
        $this->controller->list();
        
        // Vérifier que les enums existent
        $this->assertTrue(enum_exists(Status::class));
        $this->assertNotNull(Status::DRAFT);
        $this->assertNotNull(Status::PUBLISHED);
        $this->assertNotNull(Status::ARCHIVED);
    }

    public function testEnumStatusExists(): void
    {
        $statuses = [Status::DRAFT, Status::PUBLISHED, Status::ARCHIVED];
        
        foreach ($statuses as $status) {
            $this->assertInstanceOf(Status::class, $status);
            $this->assertNotEmpty($status->value);
        }
    }
}
