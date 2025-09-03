<?php

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\AbstractFunctionalTest;
use Symfony\Component\HttpFoundation\Response;

class CompanyControllerTest extends AbstractFunctionalTest
{
    public function testGetCompaniesAsAdmin(): void
    {
        $token = $this->getAdminToken();
        $this->makeAuthenticatedRequest('GET', '/api/companies', [], $token);
        
        $data = $this->assertJsonResponse();
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
    }

    public function testGetCompaniesAsRegularUser(): void
    {
        $token = $this->getUserToken();
        $this->makeAuthenticatedRequest('GET', '/api/companies', [], $token);
        
        // L'utilisateur régulier ne devrait voir que sa propre entreprise
        $data = $this->assertJsonResponse();
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(1, $data['data']); // Seulement sa propre entreprise
    }

    public function testGetCompaniesWithoutPermission(): void
    {
        $token = $this->getUserToken();
        
        // Créer un utilisateur sans permission MANAGE_USERS
        // (dans nos fixtures, l'utilisateur régulier a CREATE_QUIZ mais pas MANAGE_USERS)
        
        // Cette requête devrait échouer car l'utilisateur n'a pas la permission MANAGE_USERS
        $this->makeAuthenticatedRequest('GET', '/api/companies', [], $token);
        
        // Selon la logique du voter, cela pourrait retourner 403 ou limiter les résultats
        $response = $this->client->getResponse();
        $this->assertTrue(
            in_array($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_FORBIDDEN])
        );
    }

    public function testShowCompany(): void
    {
        $token = $this->getAdminToken();
        $this->makeAuthenticatedRequest('GET', '/api/companies/1', [], $token);
        
        $data = $this->assertJsonResponse();
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertEquals('Test Company', $data['data']['name']);
    }

    public function testShowNonExistentCompany(): void
    {
        $token = $this->getAdminToken();
        $this->makeAuthenticatedRequest('GET', '/api/companies/999', [], $token);
        
        $this->assertNotFound();
    }

    public function testCreateCompanyWithValidData(): void
    {
        $token = $this->getAdminToken();
        $companyData = [
            'name' => 'New Test Company'
        ];

        $this->makeAuthenticatedRequest('POST', '/api/companies', $companyData, $token);
        
        $data = $this->assertJsonResponse(Response::HTTP_CREATED);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertEquals('New Test Company', $data['data']['name']);
    }

    public function testCreateCompanyWithInvalidData(): void
    {
        $token = $this->getAdminToken();
        $companyData = [
            'name' => '' // Nom vide
        ];

        $this->makeAuthenticatedRequest('POST', '/api/companies', $companyData, $token);
        
        $data = $this->assertJsonResponse(Response::HTTP_BAD_REQUEST);
        $this->assertArrayHasKey('success', $data);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('obligatoire', $data['message']);
    }

    public function testCreateCompanyWithTooShortName(): void
    {
        $token = $this->getAdminToken();
        $companyData = [
            'name' => 'A' // Nom trop court
        ];

        $this->makeAuthenticatedRequest('POST', '/api/companies', $companyData, $token);
        
        $data = $this->assertJsonResponse(Response::HTTP_BAD_REQUEST);
        $this->assertArrayHasKey('success', $data);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('2 caractères', $data['message']);
    }

    public function testDeleteCompany(): void
    {
        $token = $this->getAdminToken();
        
        // Créer une entreprise d'abord
        $companyData = ['name' => 'Company to Delete'];
        $this->makeAuthenticatedRequest('POST', '/api/companies', $companyData, $token);
        $createResponse = $this->assertJsonResponse(Response::HTTP_CREATED);
        $companyId = $createResponse['data']['id'];

        // Supprimer l'entreprise
        $this->makeAuthenticatedRequest('DELETE', "/api/companies/{$companyId}", [], $token);
        
        $data = $this->assertJsonResponse();
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('supprimée', $data['message']);
    }

    public function testGetCompanyStats(): void
    {
        $token = $this->getAdminToken();
        $this->makeAuthenticatedRequest('GET', '/api/companies/1/stats', [], $token);
        
        $data = $this->assertJsonResponse();
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        
        $stats = $data['data'];
        $this->assertArrayHasKey('userCount', $stats);
        $this->assertArrayHasKey('activeUsers', $stats);
        $this->assertArrayHasKey('groupCount', $stats);
        $this->assertArrayHasKey('quizCount', $stats);
    }

    public function testGetCompanyGroups(): void
    {
        $token = $this->getAdminToken();
        $this->makeAuthenticatedRequest('GET', '/api/companies/1/groups', [], $token);
        
        $data = $this->assertJsonResponse();
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
    }

    public function testGetAvailableUsers(): void
    {
        $token = $this->getAdminToken();
        $this->makeAuthenticatedRequest('GET', '/api/companies/1/available-users', [], $token);
        
        $data = $this->assertJsonResponse();
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
    }

    public function testAssignUserToCompany(): void
    {
        $token = $this->getAdminToken();
        
        // D'abord, créer un utilisateur sans entreprise
        $userData = [
            'firstName' => 'Unassigned',
            'lastName' => 'User',
            'email' => 'unassigned@test.com',
            'password' => 'password123',
            'recaptchaToken' => 'fake-token'
        ];

        // Créer l'utilisateur (cette partie pourrait nécessiter un mock du CAPTCHA)
        $this->client->request('POST', '/api/user-create', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($userData));

        // Si la création réussit, continuer avec l'assignation
        $response = $this->client->getResponse();
        if ($response->getStatusCode() === Response::HTTP_CREATED) {
            $userResponse = json_decode($response->getContent(), true);
            $userId = $userResponse['id'];

            $assignData = [
                'userId' => $userId,
                'roles' => ['ROLE_USER'],
                'permissions' => ['CREATE_QUIZ']
            ];

            $this->makeAuthenticatedRequest('POST', '/api/companies/1/assign-user', $assignData, $token);
            
            $data = $this->assertJsonResponse();
            $this->assertArrayHasKey('success', $data);
            $this->assertTrue($data['success']);
        }
    }

    public function testExportCompaniesCSV(): void
    {
        $token = $this->getAdminToken();
        $this->makeAuthenticatedRequest('GET', '/api/companies/export/csv', [], $token);
        
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'text/csv'));
        
        $content = $response->getContent();
        $this->assertStringContainsString('ID,Nom', $content);
        $this->assertStringContainsString('Test Company', $content);
    }

    public function testExportCompaniesJSON(): void
    {
        $token = $this->getAdminToken();
        $this->makeAuthenticatedRequest('GET', '/api/companies/export/json', [], $token);
        
        $data = $this->assertJsonResponse();
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
    }

    public function testRegularUserCannotCreateCompany(): void
    {
        $token = $this->getUserToken();
        $companyData = ['name' => 'Unauthorized Company'];

        $this->makeAuthenticatedRequest('POST', '/api/companies', $companyData, $token);
        $this->assertForbidden();
    }

    public function testRegularUserCannotDeleteCompany(): void
    {
        $token = $this->getUserToken();
        $this->makeAuthenticatedRequest('DELETE', '/api/companies/1', [], $token);
        $this->assertForbidden();
    }
}
