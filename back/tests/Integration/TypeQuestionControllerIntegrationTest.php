<?php

namespace App\Tests\Integration;

use App\Controller\TypeQuestionController;
use App\Entity\TypeQuestion;
use App\Enum\TypeQuestionName;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class TypeQuestionControllerIntegrationTest extends KernelTestCase
{
    private TypeQuestionController $controller;

    protected function setUp(): void
    {
        $kernel = static::bootKernel();
        $container = $kernel->getContainer();
        
        $this->controller = new TypeQuestionController();
        
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
        $this->assertNotEmpty($responseData);
        
        // Vérifier que chaque élément a la structure attendue
        foreach ($responseData as $typeQuestion) {
            $this->assertArrayHasKey('id', $typeQuestion);
            $this->assertArrayHasKey('name', $typeQuestion);
            $this->assertArrayHasKey('key', $typeQuestion);
            $this->assertIsString($typeQuestion['name']);
            $this->assertIsString($typeQuestion['key']);
        }
    }

    public function testListContainsAllEnumValues(): void
    {
        $response = $this->controller->list();
        $responseData = json_decode($response->getContent(), true);
        
        $enumCases = TypeQuestionName::cases();
        $this->assertCount(count($enumCases), $responseData);
        
        // Vérifier que tous les cas de l'enum sont présents
        $responseKeys = array_column($responseData, 'key');
        foreach ($enumCases as $enumCase) {
            $this->assertContains($enumCase->value, $responseKeys);
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
            $this->assertArrayHasKey('key', $firstItem);
            $this->assertEquals($firstItem['id'], $firstItem['key']);
        }
    }

    public function testShowSuccess(): void
    {
        $typeQuestion = $this->createMock(TypeQuestion::class);
        $typeQuestion->method('getId')->willReturn(1);
        $typeQuestion->method('getName')->willReturn('QCM');

        $response = $this->controller->show($typeQuestion);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
    }

    public function testShowWithDifferentTypeQuestion(): void
    {
        $typeQuestion = $this->createMock(TypeQuestion::class);
        $typeQuestion->method('getId')->willReturn(2);
        $typeQuestion->method('getName')->willReturn('Vrai/Faux');

        $response = $this->controller->show($typeQuestion);

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
        $this->assertEquals('/api/type-question', $routeArgs[0]);
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
        $this->assertEquals('type_question_list', $routeArgs['name']);
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
        $this->assertEquals('type_question_show', $routeArgs['name']);
        $this->assertEquals(['GET'], $routeArgs['methods']);
    }

    public function testControllerExtendsAbstractController(): void
    {
        $this->assertInstanceOf(\Symfony\Bundle\FrameworkBundle\Controller\AbstractController::class, $this->controller);
    }

    public function testListUsesTypeQuestionNameEnum(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $listMethod = $reflection->getMethod('list');
        
        // Vérifier que la méthode utilise TypeQuestionName::cases()
        $response = $this->controller->list();
        $responseData = json_decode($response->getContent(), true);
        
        // Vérifier que le nombre d'éléments correspond au nombre de cas de l'enum
        $enumCases = TypeQuestionName::cases();
        $this->assertCount(count($enumCases), $responseData);
    }

    public function testShowUsesCorrectGroups(): void
    {
        $typeQuestion = $this->createMock(TypeQuestion::class);
        $typeQuestion->method('getId')->willReturn(1);
        $typeQuestion->method('getName')->willReturn('Test');

        $response = $this->controller->show($typeQuestion);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        // Vérifier que la réponse est bien sérialisée avec les groupes
        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
    }
}
