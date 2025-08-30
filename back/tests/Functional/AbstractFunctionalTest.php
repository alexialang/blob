<?php

namespace App\Tests\Functional;

use App\DataFixtures\TestFixtures;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractFunctionalTest extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;
    protected JWTTokenManagerInterface $jwtManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->jwtManager = static::getContainer()->get('lexik_jwt_authentication.jwt_manager');
        
        $this->loadFixtures();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }

    protected function loadFixtures(): void
    {
        // Nettoyer la base de données
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        
        // Supprimer les données existantes
        $tables = ['user_permission', 'user_answer', 'answer', 'question', 'quiz', 'user', 'company', 'category_quiz', 'type_question', 'badge', 'group_user', 'quiz_group', 'group_quiz'];
        foreach ($tables as $table) {
            try {
                $connection->executeStatement("TRUNCATE TABLE $table");
            } catch (\Exception $e) {
                // Ignorer les erreurs de tables inexistantes
            }
        }
        
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

        // Charger les fixtures
        $fixtures = new TestFixtures(
            static::getContainer()->get('security.user_password_hasher')
        );
        $fixtures->load($this->entityManager);
    }

    protected function getAdminToken(): string
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@test.com']);
        return $this->jwtManager->create($user);
    }

    protected function getUserToken(): string
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'user@test.com']);
        return $this->jwtManager->create($user);
    }

    protected function makeAuthenticatedRequest(
        string $method,
        string $uri,
        array $data = [],
        string $token = null
    ): void {
        $headers = [];
        if ($token) {
            $headers['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }

        if (!empty($data)) {
            $headers['CONTENT_TYPE'] = 'application/json';
            $this->client->request($method, $uri, [], [], $headers, json_encode($data));
        } else {
            $this->client->request($method, $uri, [], [], $headers);
        }
    }

    protected function assertJsonResponse(int $expectedStatusCode = Response::HTTP_OK): array
    {
        $response = $this->client->getResponse();
        $this->assertEquals($expectedStatusCode, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        
        $content = $response->getContent();
        $this->assertJson($content);
        
        return json_decode($content, true);
    }

    protected function assertValidationError(array $expectedFields = []): array
    {
        $data = $this->assertJsonResponse(Response::HTTP_BAD_REQUEST);
        $this->assertArrayHasKey('error', $data);
        
        if (!empty($expectedFields)) {
            $this->assertArrayHasKey('details', $data);
            foreach ($expectedFields as $field) {
                $this->assertStringContainsString($field, json_encode($data['details']));
            }
        }
        
        return $data;
    }

    protected function assertUnauthorized(): array
    {
        return $this->assertJsonResponse(Response::HTTP_UNAUTHORIZED);
    }

    protected function assertForbidden(): array
    {
        return $this->assertJsonResponse(Response::HTTP_FORBIDDEN);
    }

    protected function assertNotFound(): array
    {
        return $this->assertJsonResponse(Response::HTTP_NOT_FOUND);
    }
}
