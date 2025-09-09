<?php

namespace App\Tests\Unit\Controller;

use App\Controller\CategoryQuizController;
use App\Entity\CategoryQuiz;
use App\Service\CategoryQuizService;
use PHPUnit\Framework\TestCase;

class CategoryQuizControllerBasicTest extends TestCase
{
    private CategoryQuizController $controller;
    private CategoryQuizService $categoryQuizService;

    protected function setUp(): void
    {
        $this->categoryQuizService = $this->createMock(CategoryQuizService::class);
        $this->controller = new CategoryQuizController($this->categoryQuizService);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(CategoryQuizController::class, $this->controller);
    }

    public function testControllerHasRequiredMethods(): void
    {
        $this->assertTrue(method_exists($this->controller, 'index'));
        $this->assertTrue(method_exists($this->controller, 'show'));
    }
}
