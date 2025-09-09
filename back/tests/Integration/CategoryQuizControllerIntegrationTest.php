<?php

namespace App\Tests\Integration;

use App\Controller\CategoryQuizController;
use App\Entity\CategoryQuiz;
use App\Service\CategoryQuizService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class CategoryQuizControllerIntegrationTest extends KernelTestCase
{
    private CategoryQuizController $controller;
    private CategoryQuizService $categoryQuizService;

    protected function setUp(): void
    {
        $kernel = static::bootKernel();
        $container = $kernel->getContainer();
        
        $this->categoryQuizService = $this->createMock(CategoryQuizService::class);
        
        $this->controller = new CategoryQuizController($this->categoryQuizService);
        
        // Injecter le container pour que les méthodes json() fonctionnent
        $this->controller->setContainer($container);
    }

    public function testIndexSuccess(): void
    {
        $mockCategories = [
            ['id' => 1, 'name' => 'Géographie', 'description' => 'Quiz de géographie'],
            ['id' => 2, 'name' => 'Histoire', 'description' => 'Quiz d\'histoire'],
            ['id' => 3, 'name' => 'Sciences', 'description' => 'Quiz de sciences']
        ];

        $this->categoryQuizService->expects($this->once())
            ->method('list')
            ->willReturn($mockCategories);

        $response = $this->controller->index();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(3, $responseData);
        $this->assertEquals('Géographie', $responseData[0]['name']);
        $this->assertEquals('Histoire', $responseData[1]['name']);
        $this->assertEquals('Sciences', $responseData[2]['name']);
    }

    public function testIndexEmptyList(): void
    {
        $this->categoryQuizService->expects($this->once())
            ->method('list')
            ->willReturn([]);

        $response = $this->controller->index();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertEmpty($responseData);
    }

    public function testIndexServiceException(): void
    {
        $this->categoryQuizService->expects($this->once())
            ->method('list')
            ->willThrowException(new \Exception('Service indisponible'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Service indisponible');

        $this->controller->index();
    }

    public function testShowSuccess(): void
    {
        $category = $this->createMock(CategoryQuiz::class);
        $category->method('getId')->willReturn(1);
        $category->method('getName')->willReturn('Géographie');

        $response = $this->controller->show($category);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
    }

    public function testShowWithValidCategory(): void
    {
        $category = $this->createMock(CategoryQuiz::class);
        $category->method('getId')->willReturn(2);
        $category->method('getName')->willReturn('Histoire');

        $response = $this->controller->show($category);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
    }

    public function testShowWithComplexCategory(): void
    {
        $category = $this->createMock(CategoryQuiz::class);
        $category->method('getId')->willReturn(42);
        $category->method('getName')->willReturn('Mathématiques Avancées');

        $response = $this->controller->show($category);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
    }

    public function testControllerHasCorrectRoute(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $attributes = $reflection->getAttributes();
        
        $this->assertCount(1, $attributes);
        $this->assertEquals('Symfony\Component\Routing\Annotation\Route', $attributes[0]->getName());
        
        $routeArgs = $attributes[0]->getArguments();
        $this->assertEquals('/api/category-quiz', $routeArgs[0]);
    }

    public function testIndexMethodHasCorrectRoute(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $indexMethod = $reflection->getMethod('index');
        $attributes = $indexMethod->getAttributes();
        
        $this->assertCount(1, $attributes);
        $this->assertEquals('Symfony\Component\Routing\Annotation\Route', $attributes[0]->getName());
        
        $routeArgs = $attributes[0]->getArguments();
        $this->assertEquals('', $routeArgs[0]); // Route vide pour l'index
        $this->assertEquals('category_quiz_index', $routeArgs['name']);
        $this->assertEquals(['GET'], $routeArgs['methods']);
    }

    public function testShowMethodHasCorrectRoute(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $showMethod = $reflection->getMethod('show');
        $attributes = $showMethod->getAttributes();
        
        $this->assertCount(1, $attributes);
        $this->assertEquals('Symfony\Component\Routing\Annotation\Route', $attributes[0]->getName());
        
        $routeArgs = $attributes[0]->getArguments();
        $this->assertEquals('/{id}', $routeArgs[0]);
        $this->assertEquals('category_quiz_show', $routeArgs['name']);
        $this->assertEquals(['GET'], $routeArgs['methods']);
    }

    public function testControllerExtendsAbstractController(): void
    {
        $this->assertInstanceOf(\Symfony\Bundle\FrameworkBundle\Controller\AbstractController::class, $this->controller);
    }

    public function testControllerUsesCorrectService(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('categoryQuizService', $parameters[0]->getName());
        $this->assertEquals(CategoryQuizService::class, $parameters[0]->getType()->getName());
    }
}
