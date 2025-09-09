<?php

namespace App\Tests\Unit\Controller;

use App\Controller\TypeQuestionController;
use PHPUnit\Framework\TestCase;

class TypeQuestionControllerTest extends TestCase
{
    public function testTypeQuestionControllerCanBeInstantiated(): void
    {
        $controller = new TypeQuestionController();
        $this->assertInstanceOf(TypeQuestionController::class, $controller);
    }

    public function testTypeQuestionControllerHasMethods(): void
    {
        $controller = new TypeQuestionController();
        $this->assertTrue(method_exists($controller, 'list'));
        $this->assertTrue(method_exists($controller, 'show'));
    }
}