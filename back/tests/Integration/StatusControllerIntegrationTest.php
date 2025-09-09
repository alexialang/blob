<?php

namespace App\Tests\Integration;

use App\Controller\StatusController;
use App\Enum\Status;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class StatusControllerIntegrationTest extends KernelTestCase
{
    private StatusController $controller;

    protected function setUp(): void
    {
        $kernel = static::bootKernel();
        $container = $kernel->getContainer();
        
        $this->controller = new StatusController();
        
        // Injecter le container pour que les méthodes json() fonctionnent
        $this->controller->setContainer($container);
    }

    public function testListSuccess(): void
    {
        $response = $this->controller->list();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(3, $responseData);
        
        // Vérifier que chaque élément a la structure attendue
        foreach ($responseData as $status) {
            $this->assertArrayHasKey('id', $status);
            $this->assertArrayHasKey('name', $status);
            $this->assertArrayHasKey('value', $status);
            $this->assertIsInt($status['id']);
            $this->assertIsString($status['name']);
            $this->assertIsString($status['value']);
        }
    }

    public function testListContainsAllStatusValues(): void
    {
        $response = $this->controller->list();
        $responseData = json_decode($response->getContent(), true);
        
        $expectedStatuses = [Status::DRAFT, Status::PUBLISHED, Status::ARCHIVED];
        $this->assertCount(count($expectedStatuses), $responseData);
        
        // Vérifier que tous les statuts attendus sont présents
        $responseValues = array_column($responseData, 'value');
        foreach ($expectedStatuses as $expectedStatus) {
            $this->assertContains($expectedStatus->value, $responseValues);
        }
    }

    public function testListResponseStructure(): void
    {
        $response = $this->controller->list();
        $responseData = json_decode($response->getContent(), true);
        
        // Vérifier la structure d'un élément
        if (!empty($responseData)) {
            $firstItem = $responseData[0];
            $this->assertArrayHasKey('id', $firstItem);
            $this->assertArrayHasKey('name', $firstItem);
            $this->assertArrayHasKey('value', $firstItem);
            $this->assertGreaterThan(0, $firstItem['id']);
        }
    }

    public function testControllerHasCorrectRoute(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $attributes = $reflection->getAttributes();
        
        $this->assertCount(1, $attributes);
        $this->assertEquals('Symfony\Component\Routing\Annotation\Route', $attributes[0]->getName());
        
        $routeArgs = $attributes[0]->getArguments();
        $this->assertEquals('/api/status', $routeArgs[0]);
    }

    public function testListMethodHasCorrectRoute(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $listMethod = $reflection->getMethod('list');
        $attributes = $listMethod->getAttributes();
        
        $this->assertCount(1, $attributes);
        $this->assertEquals('Symfony\Component\Routing\Annotation\Route', $attributes[0]->getName());
        
        $routeArgs = $attributes[0]->getArguments();
        $this->assertEquals('/list', $routeArgs[0]);
        $this->assertEquals('status_list', $routeArgs['name']);
        $this->assertEquals(['GET'], $routeArgs['methods']);
    }

    public function testControllerExtendsAbstractController(): void
    {
        $this->assertInstanceOf(\Symfony\Bundle\FrameworkBundle\Controller\AbstractController::class, $this->controller);
    }

    public function testListUsesStatusEnum(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $listMethod = $reflection->getMethod('list');
        
        // Vérifier que la méthode utilise les enums Status
        $response = $this->controller->list();
        $responseData = json_decode($response->getContent(), true);
        
        // Vérifier que le nombre d'éléments correspond au nombre de statuts
        $expectedStatuses = [Status::DRAFT, Status::PUBLISHED, Status::ARCHIVED];
        $this->assertCount(count($expectedStatuses), $responseData);
    }

    public function testListResponseContainsCorrectStatuses(): void
    {
        $response = $this->controller->list();
        $responseData = json_decode($response->getContent(), true);
        
        // Vérifier que les statuts spécifiques sont présents
        $statusValues = array_column($responseData, 'value');
        $this->assertContains(Status::DRAFT->value, $statusValues);
        $this->assertContains(Status::PUBLISHED->value, $statusValues);
        $this->assertContains(Status::ARCHIVED->value, $statusValues);
    }

    public function testListResponseHasCorrectIds(): void
    {
        $response = $this->controller->list();
        $responseData = json_decode($response->getContent(), true);
        
        // Vérifier que les IDs sont séquentiels et commencent à 1
        $ids = array_column($responseData, 'id');
        $this->assertEquals([1, 2, 3], $ids);
    }

    public function testListResponseHasCorrectNames(): void
    {
        $response = $this->controller->list();
        $responseData = json_decode($response->getContent(), true);
        
        // Vérifier que les noms correspondent aux enums
        $names = array_column($responseData, 'name');
        $this->assertContains(Status::DRAFT->getName(), $names);
        $this->assertContains(Status::PUBLISHED->getName(), $names);
        $this->assertContains(Status::ARCHIVED->getName(), $names);
    }
}
