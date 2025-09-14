<?php

namespace App\Tests\Unit\Controller;

use App\Controller\TypeQuestionController;
use App\Repository\TypeQuestionRepository;
use PHPUnit\Framework\TestCase;

class TypeQuestionControllerTest extends TestCase
{
    public function testTypeQuestionControllerCanBeInstantiated(): void
    {
        $repository = $this->createMock(TypeQuestionRepository::class);
        $controller = new TypeQuestionController($repository);
        $this->assertInstanceOf(TypeQuestionController::class, $controller);
    }

    public function testTypeQuestionControllerHasMethods(): void
    {
        $repository = $this->createMock(TypeQuestionRepository::class);
        $controller = new TypeQuestionController($repository);
        $this->assertTrue(method_exists($controller, 'list'));
        $this->assertTrue(method_exists($controller, 'show'));
    }
}
