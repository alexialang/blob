<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiCategoryQuizTest extends WebTestCase
{
    public function testGetCategoryQuizList(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/category-quiz');
        
        $response = $client->getResponse();
        
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }
    
    public function testGetCategoryQuizById(): void
    {
        $client = static::createClient();
        
        // Test avec un ID qui pourrait exister
        $client->request('GET', '/api/category-quiz/1');
        
        $response = $client->getResponse();
        
        // Soit OK (existe), soit NOT_FOUND (n'existe pas)
        $this->assertTrue(
            $response->getStatusCode() === Response::HTTP_OK || 
            $response->getStatusCode() === Response::HTTP_NOT_FOUND
        );
        
        $this->assertJson($response->getContent());
    }
    
    public function testGetCategoryQuizWithInvalidId(): void
    {
        $client = static::createClient();
        
        // Test avec un ID invalide (négatif)
        $client->request('GET', '/api/category-quiz/-1');
        
        $response = $client->getResponse();
        
        // Doit retourner une erreur (400 ou 404)
        $this->assertTrue(
            $response->getStatusCode() === Response::HTTP_BAD_REQUEST || 
            $response->getStatusCode() === Response::HTTP_NOT_FOUND
        );
    }
    
    public function testApiResponseHeaders(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/category-quiz');
        
        $response = $client->getResponse();
        
        // Vérifier les en-têtes de sécurité qu'on a ajoutés
        $this->assertTrue($response->headers->has('x-frame-options'));
        $this->assertTrue($response->headers->has('x-content-type-options'));
        $this->assertTrue($response->headers->has('strict-transport-security'));
        
        $this->assertEquals('application/json', $response->headers->get('content-type'));
    }
    
    public function testCorsHeaders(): void
    {
        $client = static::createClient();
        
        // Test requête OPTIONS (preflight CORS)
        $client->request('OPTIONS', '/api/category-quiz', [], [], [
            'HTTP_ORIGIN' => 'https://angular.dev.local',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ]);
        
        $response = $client->getResponse();
        
        // CORS doit être configuré
        $this->assertTrue(
            $response->getStatusCode() === Response::HTTP_OK || 
            $response->getStatusCode() === Response::HTTP_NO_CONTENT
        );
    }
}
