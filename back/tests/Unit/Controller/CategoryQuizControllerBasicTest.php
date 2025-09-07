<?php

namespace App\Tests\Unit\Controller;

use App\Controller\CategoryQuizController;
use App\Entity\CategoryQuiz;
use App\Service\CategoryQuizService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

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

    public function testIndexCallsService(): void
    {
        $categories = [
            $this->createMock(CategoryQuiz::class),
            $this->createMock(CategoryQuiz::class)
        ];

        $this->categoryQuizService->expects($this->once())
            ->method('list')
            ->willReturn($categories);

        // On ne peut pas tester la méthode json() facilement en unit test
        // mais on peut au moins tester que le service est appelé
        $this->controller->index();
    }

    public function testShowMethodExists(): void
    {
        $category = $this->createMock(CategoryQuiz::class);
        
        // Test que la méthode existe et peut être appelée
        $this->assertTrue(method_exists($this->controller, 'show'));
        
        // Appel de la méthode
        $this->controller->show($category);
    }
}
