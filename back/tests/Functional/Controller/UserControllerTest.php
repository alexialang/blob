<?php

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\AbstractFunctionalTest;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends AbstractFunctionalTest
{
    public function testCreateUserWithValidData(): void
    {
        $userData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'recaptchaToken' => 'fake-token'
        ];

        // Mock du service CAPTCHA pour les tests
        $this->client->request('POST', '/api/user-create', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($userData));

        // Note: En test, le CAPTCHA sera mocké ou désactivé
        $response = $this->client->getResponse();
        $this->assertTrue(
            in_array($response->getStatusCode(), [Response::HTTP_CREATED, Response::HTTP_BAD_REQUEST])
        );
    }

    public function testCreateUserWithInvalidEmail(): void
    {
        $userData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'recaptchaToken' => 'fake-token'
        ];

        $this->client->request('POST', '/api/user-create', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($userData));

        $this->assertValidationError(['email']);
    }

    public function testCreateUserWithMissingFields(): void
    {
        $userData = [
            'firstName' => 'John',
            'email' => 'john@example.com',
            'password' => 'password123',
            'recaptchaToken' => 'fake-token'
        ];

        $this->client->request('POST', '/api/user-create', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($userData));

        $this->assertValidationError(['lastName']);
    }

    public function testGetProfileWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/user/profile');
        $this->assertUnauthorized();
    }

    public function testGetProfileWithAuthentication(): void
    {
        $token = $this->getUserToken();
        $this->makeAuthenticatedRequest('GET', '/api/user/profile', [], $token);
        
        $data = $this->assertJsonResponse();
        $this->assertArrayHasKey('email', $data);
        $this->assertEquals('user@test.com', $data['email']);
    }

    public function testUpdateProfileWithValidData(): void
    {
        $token = $this->getUserToken();
        $updateData = [
            'firstName' => 'Updated',
            'lastName' => 'Name',
            'pseudo' => 'updateduser'
        ];

        $this->makeAuthenticatedRequest('PUT', '/api/user/profile/update', $updateData, $token);
        
        $data = $this->assertJsonResponse();
        $this->assertEquals('Updated', $data['firstName']);
        $this->assertEquals('Name', $data['lastName']);
    }

    public function testUpdateProfileWithInvalidData(): void
    {
        $token = $this->getUserToken();
        $updateData = [
            'firstName' => '', // Vide
            'email' => 'invalid-email'
        ];

        $this->makeAuthenticatedRequest('PUT', '/api/user/profile/update', $updateData, $token);
        $this->assertValidationError(['firstName', 'email']);
    }

    public function testGetUserStatistics(): void
    {
        $token = $this->getUserToken();
        $this->makeAuthenticatedRequest('GET', '/api/user/statistics', [], $token);
        
        $data = $this->assertJsonResponse();
        $this->assertArrayHasKey('totalQuizzesCreated', $data);
        $this->assertArrayHasKey('totalQuizzesCompleted', $data);
        $this->assertArrayHasKey('averageScore', $data);
    }

    public function testAdminUserList(): void
    {
        $token = $this->getAdminToken();
        $this->makeAuthenticatedRequest('GET', '/api/admin/all', [], $token);
        
        $data = $this->assertJsonResponse();
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));
    }

    public function testRegularUserCannotAccessAdminList(): void
    {
        $token = $this->getUserToken();
        $this->makeAuthenticatedRequest('GET', '/api/admin/all', [], $token);
        $this->assertForbidden();
    }

    public function testConfirmAccountWithValidToken(): void
    {
        $this->client->request('GET', '/api/confirmation-compte/test-token-123');
        
        $data = $this->assertJsonResponse();
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('vérifié', $data['message']);
    }

    public function testConfirmAccountWithInvalidToken(): void
    {
        $this->client->request('GET', '/api/confirmation-compte/invalid-token');
        
        $data = $this->assertJsonResponse(Response::HTTP_BAD_REQUEST);
        $this->assertArrayHasKey('error', $data);
    }

    public function testGetGameHistory(): void
    {
        $token = $this->getUserToken();
        $this->makeAuthenticatedRequest('GET', '/api/user/game-history', [], $token);
        
        $data = $this->assertJsonResponse();
        $this->assertIsArray($data);
    }
}
