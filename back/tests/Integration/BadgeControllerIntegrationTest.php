<?php

namespace App\Tests\Integration;

use App\Controller\BadgeController;
use App\Entity\Badge;
use App\Service\BadgeService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class BadgeControllerIntegrationTest extends KernelTestCase
{
    private BadgeController $controller;
    private BadgeService $badgeService;

    protected function setUp(): void
    {
        $kernel = static::bootKernel();
        $container = $kernel->getContainer();
        
        $this->badgeService = $this->createMock(BadgeService::class);
        $this->controller = new BadgeController($this->badgeService);
        
        // Injecter le container pour que les méthodes json() fonctionnent
        $this->controller->setContainer($container);
    }

    public function testIndexSuccess(): void
    {
        $badges = [
            ['id' => 1, 'name' => 'Premier Quiz', 'description' => 'Complétez votre premier quiz'],
            ['id' => 2, 'name' => 'Expert', 'description' => 'Complétez 10 quiz']
        ];

        $this->badgeService->method('list')->willReturn($badges);

        $response = $this->controller->index();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(2, $responseData);
    }

    public function testIndexWithEmptyList(): void
    {
        $this->badgeService->method('list')->willReturn([]);

        $response = $this->controller->index();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertEmpty($responseData);
    }

    public function testShowSuccess(): void
    {
        $badge = $this->createMock(Badge::class);
        $badge->method('getId')->willReturn(1);
        $badge->method('getName')->willReturn('Premier Quiz');

        $response = $this->controller->show($badge);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
    }

    public function testInitializeSuccess(): void
    {
        $this->badgeService->method('initializeBadges')->willReturnCallback(function() {
            // Simule l'exécution de la méthode void
        });

        $response = $this->controller->initialize();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Badges initialisés avec succès', $responseData['message']);
    }

    public function testInitializeWithException(): void
    {
        $this->badgeService->method('initializeBadges')
            ->willThrowException(new \Exception('Erreur de base de données'));

        $response = $this->controller->initialize();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('Erreur lors de l\'initialisation', $responseData['error']);
    }

    public function testControllerHasCorrectRoute(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $attributes = $reflection->getAttributes();
        
        $this->assertCount(1, $attributes);
        $this->assertEquals('Symfony\Component\Routing\Annotation\Route', $attributes[0]->getName());
        
        $routeArgs = $attributes[0]->getArguments();
        $this->assertEquals('/api/badge', $routeArgs[0]);
    }

    public function testIndexMethodHasCorrectRoute(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $indexMethod = $reflection->getMethod('index');
        $attributes = $indexMethod->getAttributes();
        
        $this->assertCount(1, $attributes);
        $this->assertEquals('Symfony\Component\Routing\Annotation\Route', $attributes[0]->getName());
        
        $routeArgs = $attributes[0]->getArguments();
        $this->assertEquals('/', $routeArgs[0]);
        $this->assertEquals('badge_index', $routeArgs['name']);
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
        $this->assertEquals('badge_show', $routeArgs['name']);
        $this->assertEquals(['GET'], $routeArgs['methods']);
    }

    public function testInitializeMethodHasCorrectRoute(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $initializeMethod = $reflection->getMethod('initialize');
        $attributes = $initializeMethod->getAttributes();
        
        $this->assertCount(2, $attributes);
        
        // Vérifier l'attribut Route
        $routeAttribute = null;
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Symfony\Component\Routing\Annotation\Route') {
                $routeAttribute = $attribute;
                break;
            }
        }
        
        $this->assertNotNull($routeAttribute);
        $routeArgs = $routeAttribute->getArguments();
        $this->assertEquals('/initialize', $routeArgs[0]);
        $this->assertEquals('badge_initialize', $routeArgs['name']);
        $this->assertEquals(['POST'], $routeArgs['methods']);
    }

    public function testControllerExtendsAbstractController(): void
    {
        $this->assertInstanceOf(\Symfony\Bundle\FrameworkBundle\Controller\AbstractController::class, $this->controller);
    }

    public function testControllerUsesBadgeService(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('badgeService', $parameters[0]->getName());
        $this->assertEquals(BadgeService::class, $parameters[0]->getType()->getName());
    }
}
